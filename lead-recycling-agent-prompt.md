# AI Agent Prompt: Lead Recycling & Customer Historical System

## System Context

You are an AI agent responsible for implementing and managing the **Lead Recycling & Customer Historical Tracking System** for the Thirdynal Warehouse Ops System. This system handles the complete lifecycle of waybills → leads → sales/outcomes → J&T integration → historical tracking.

---

## Core Data Flow Understanding

```
┌─────────────┐    ┌─────────────┐    ┌──────────────────┐    ┌─────────────┐
│   Upload    │───▶│   Waybills  │───▶│  Lead Farming    │───▶│    Leads    │
│  Waybills   │    │   (Raw)     │    │  (Conversion)    │    │  (Active)   │
└─────────────┘    └─────────────┘    └──────────────────┘    └─────────────┘
                                                                      │
                   ┌──────────────────────────────────────────────────┘
                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           LEAD OUTCOMES                                      │
├─────────────┬─────────────┬─────────────┬─────────────┬─────────────────────┤
│    SALE     │   REORDER   │  NO ANSWER  │   DECLINED  │   CALLBACK          │
│  (Success)  │  (Repeat)   │   (Retry)   │   (Lost)    │   (Scheduled)       │
└─────────────┴─────────────┴─────────────┴─────────────┴─────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        J&T INTEGRATION LAYER                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│  • Export to J&T (New Orders)                                               │
│  • Pull J&T Data (Status Updates)                                           │
│  • Reconcile: UPDATE existing records OR INSERT new records                 │
└─────────────────────────────────────────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                    CUSTOMER HISTORICAL SYSTEM                                │
├─────────────────────────────────────────────────────────────────────────────┤
│  • Unified Customer Profile (by phone/name matching)                        │
│  • Complete Order History                                                    │
│  • Performance Metrics (delivered, returned, pending rates)                 │
│  • Lead Recycling Eligibility Scoring                                       │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Database Schema Design

### 1. Customer Master Table (NEW)
```sql
CREATE TABLE customers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    
    -- Identity (for matching/deduplication)
    phone_primary VARCHAR(20) NOT NULL,
    phone_secondary VARCHAR(20),
    name_normalized VARCHAR(255), -- lowercase, trimmed for matching
    name_display VARCHAR(255),    -- original casing for display
    
    -- Location data (aggregated from orders)
    primary_address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    
    -- Performance Metrics (denormalized for fast access)
    total_orders INTEGER DEFAULT 0,
    total_delivered INTEGER DEFAULT 0,
    total_returned INTEGER DEFAULT 0,
    total_pending INTEGER DEFAULT 0,
    total_in_transit INTEGER DEFAULT 0,
    
    -- Financial metrics
    total_order_value DECIMAL(12,2) DEFAULT 0,
    total_delivered_value DECIMAL(12,2) DEFAULT 0,
    total_returned_value DECIMAL(12,2) DEFAULT 0,
    
    -- Computed scores
    delivery_success_rate DECIMAL(5,2) DEFAULT 0, -- (delivered/total)*100
    customer_score INTEGER DEFAULT 50,             -- 0-100 scoring
    risk_level VARCHAR(20) DEFAULT 'UNKNOWN',      -- LOW, MEDIUM, HIGH, BLACKLIST
    
    -- Lead recycling metadata
    times_contacted INTEGER DEFAULT 0,
    last_contact_date TIMESTAMP,
    last_order_date TIMESTAMP,
    last_delivery_date TIMESTAMP,
    recycling_eligible BOOLEAN DEFAULT TRUE,
    recycling_cooldown_until TIMESTAMP,
    
    -- Timestamps
    first_seen_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    UNIQUE(phone_primary)
);

CREATE INDEX idx_customers_phone ON customers(phone_primary);
CREATE INDEX idx_customers_phone_secondary ON customers(phone_secondary);
CREATE INDEX idx_customers_risk ON customers(risk_level);
CREATE INDEX idx_customers_score ON customers(customer_score DESC);
CREATE INDEX idx_customers_recycling ON customers(recycling_eligible, recycling_cooldown_until);
```

### 2. Customer Order History Table (NEW)
```sql
CREATE TABLE customer_order_history (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id UUID REFERENCES customers(id),
    
    -- Order identification
    waybill_number VARCHAR(50) NOT NULL,
    source_type VARCHAR(20), -- 'UPLOAD', 'LEAD_CONVERSION', 'REORDER'
    
    -- Product info
    product_name VARCHAR(255),
    product_id UUID,
    weight DECIMAL(10,2),
    declared_value DECIMAL(12,2),
    cod_amount DECIMAL(12,2),
    
    -- Status tracking
    current_status VARCHAR(50),
    status_history JSONB DEFAULT '[]',
    
    -- J&T specific
    jnt_waybill VARCHAR(50),
    jnt_last_sync TIMESTAMP,
    jnt_raw_data JSONB,
    
    -- Lead info (if originated from lead)
    lead_id UUID,
    lead_outcome VARCHAR(50),
    lead_agent VARCHAR(100),
    
    -- Timestamps
    order_date TIMESTAMP,
    shipped_date TIMESTAMP,
    delivered_date TIMESTAMP,
    returned_date TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    UNIQUE(waybill_number)
);

CREATE INDEX idx_order_history_customer ON customer_order_history(customer_id);
CREATE INDEX idx_order_history_waybill ON customer_order_history(waybill_number);
CREATE INDEX idx_order_history_status ON customer_order_history(current_status);
CREATE INDEX idx_order_history_dates ON customer_order_history(order_date DESC);
```

### 3. Lead Recycling Pool Table (NEW)
```sql
CREATE TABLE lead_recycling_pool (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id UUID REFERENCES customers(id),
    
    -- Source tracking
    source_waybill VARCHAR(50),
    source_lead_id UUID,
    original_outcome VARCHAR(50), -- What happened last time
    
    -- Recycling metadata
    recycle_reason VARCHAR(100),  -- 'RETURNED_DELIVERABLE', 'NO_ANSWER_RETRY', 'REORDER_CANDIDATE'
    recycle_count INTEGER DEFAULT 1,
    priority_score INTEGER DEFAULT 50, -- Higher = contact first
    
    -- Scheduling
    available_from TIMESTAMP DEFAULT NOW(),
    expires_at TIMESTAMP,
    
    -- Assignment
    assigned_to VARCHAR(100),
    assigned_at TIMESTAMP,
    
    -- Status
    pool_status VARCHAR(20) DEFAULT 'AVAILABLE', -- AVAILABLE, ASSIGNED, CONVERTED, EXPIRED, EXHAUSTED
    
    -- Outcome when processed
    processed_at TIMESTAMP,
    processed_outcome VARCHAR(50),
    
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_recycling_pool_status ON lead_recycling_pool(pool_status, available_from);
CREATE INDEX idx_recycling_pool_customer ON lead_recycling_pool(customer_id);
CREATE INDEX idx_recycling_pool_priority ON lead_recycling_pool(priority_score DESC);
```

### 4. Lead Status History Table (Enhanced)
```sql
CREATE TABLE lead_status_history (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    lead_id UUID NOT NULL,
    customer_id UUID REFERENCES customers(id),
    
    -- Status change
    previous_status VARCHAR(50),
    new_status VARCHAR(50),
    
    -- Context
    changed_by VARCHAR(100),
    change_reason TEXT,
    notes TEXT,
    
    -- Call/Contact metadata
    contact_method VARCHAR(50), -- 'CALL', 'SMS', 'SYSTEM'
    call_duration_seconds INTEGER,
    
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_lead_history_lead ON lead_status_history(lead_id);
CREATE INDEX idx_lead_history_customer ON lead_status_history(customer_id);
```

---

## Agent Tasks & Capabilities

### Task 1: Customer Identification & Deduplication

When processing any waybill or lead, identify or create the customer record:

```javascript
async function identifyOrCreateCustomer(orderData) {
    // Normalize phone number
    const phoneNormalized = normalizePhone(orderData.phone);
    
    // Try exact phone match first
    let customer = await db.customers.findOne({ 
        phone_primary: phoneNormalized 
    });
    
    // Try secondary phone
    if (!customer) {
        customer = await db.customers.findOne({ 
            phone_secondary: phoneNormalized 
        });
    }
    
    // Fuzzy match on name + partial phone (last 7 digits)
    if (!customer) {
        const phoneSuffix = phoneNormalized.slice(-7);
        const nameNormalized = normalizeName(orderData.receiver_name);
        
        customer = await db.customers.findOne({
            name_normalized: nameNormalized,
            phone_primary: { $regex: phoneSuffix + '$' }
        });
    }
    
    // Create new customer if not found
    if (!customer) {
        customer = await db.customers.create({
            phone_primary: phoneNormalized,
            name_normalized: normalizeName(orderData.receiver_name),
            name_display: orderData.receiver_name,
            primary_address: orderData.address,
            city: orderData.city,
            province: orderData.province,
            first_seen_at: new Date()
        });
    }
    
    return customer;
}
```

### Task 2: Order Status Synchronization with J&T

```javascript
async function syncWithJNT(waybillNumbers) {
    // Fetch data from J&T API
    const jntData = await jntApi.bulkTrack(waybillNumbers);
    
    for (const tracking of jntData) {
        const order = await db.customer_order_history.findOne({
            waybill_number: tracking.waybill
        });
        
        if (order) {
            // UPDATE existing record
            const statusChanged = order.current_status !== tracking.status;
            
            await db.customer_order_history.update({
                waybill_number: tracking.waybill
            }, {
                current_status: tracking.status,
                jnt_last_sync: new Date(),
                jnt_raw_data: tracking,
                status_history: [...order.status_history, {
                    status: tracking.status,
                    timestamp: new Date(),
                    location: tracking.location
                }],
                // Set completion dates
                delivered_date: tracking.status === 'DELIVERED' ? new Date() : order.delivered_date,
                returned_date: tracking.status === 'RETURNED' ? new Date() : order.returned_date,
                updated_at: new Date()
            });
            
            // Update customer metrics if status changed
            if (statusChanged) {
                await updateCustomerMetrics(order.customer_id);
                
                // Check for recycling eligibility
                if (['RETURNED', 'FAILED_DELIVERY'].includes(tracking.status)) {
                    await evaluateForRecycling(order);
                }
            }
        } else {
            // INSERT new record (waybill from J&T not in our system)
            const customer = await identifyOrCreateCustomer({
                phone: tracking.receiver_phone,
                receiver_name: tracking.receiver_name,
                address: tracking.receiver_address,
                city: tracking.destination_city
            });
            
            await db.customer_order_history.create({
                customer_id: customer.id,
                waybill_number: tracking.waybill,
                source_type: 'JNT_IMPORT',
                current_status: tracking.status,
                jnt_waybill: tracking.waybill,
                jnt_last_sync: new Date(),
                jnt_raw_data: tracking,
                order_date: tracking.created_date,
                created_at: new Date()
            });
            
            await updateCustomerMetrics(customer.id);
        }
    }
}
```

### Task 3: Customer Metrics Recalculation

```javascript
async function updateCustomerMetrics(customerId) {
    const orders = await db.customer_order_history.find({ 
        customer_id: customerId 
    });
    
    const metrics = {
        total_orders: orders.length,
        total_delivered: orders.filter(o => o.current_status === 'DELIVERED').length,
        total_returned: orders.filter(o => ['RETURNED', 'RTS'].includes(o.current_status)).length,
        total_pending: orders.filter(o => o.current_status === 'PENDING').length,
        total_in_transit: orders.filter(o => ['IN_TRANSIT', 'DELIVERING'].includes(o.current_status)).length,
        total_order_value: orders.reduce((sum, o) => sum + (o.cod_amount || 0), 0),
        total_delivered_value: orders
            .filter(o => o.current_status === 'DELIVERED')
            .reduce((sum, o) => sum + (o.cod_amount || 0), 0),
        total_returned_value: orders
            .filter(o => ['RETURNED', 'RTS'].includes(o.current_status))
            .reduce((sum, o) => sum + (o.cod_amount || 0), 0),
        last_order_date: orders.length > 0 ? 
            Math.max(...orders.map(o => new Date(o.order_date))) : null,
        last_delivery_date: orders
            .filter(o => o.delivered_date)
            .reduce((latest, o) => {
                const d = new Date(o.delivered_date);
                return d > latest ? d : latest;
            }, new Date(0))
    };
    
    // Calculate delivery success rate
    const completedOrders = metrics.total_delivered + metrics.total_returned;
    metrics.delivery_success_rate = completedOrders > 0 
        ? (metrics.total_delivered / completedOrders) * 100 
        : 0;
    
    // Calculate customer score (0-100)
    metrics.customer_score = calculateCustomerScore(metrics);
    
    // Determine risk level
    metrics.risk_level = determineRiskLevel(metrics);
    
    await db.customers.update({ id: customerId }, {
        ...metrics,
        updated_at: new Date()
    });
}

function calculateCustomerScore(metrics) {
    let score = 50; // Base score
    
    // Delivery success rate impact (max ±30)
    if (metrics.total_orders >= 3) {
        score += (metrics.delivery_success_rate - 50) * 0.6; // -30 to +30
    }
    
    // Order volume bonus (max +10)
    score += Math.min(metrics.total_orders * 2, 10);
    
    // Recent activity bonus (max +10)
    if (metrics.last_delivery_date) {
        const daysSinceDelivery = (Date.now() - metrics.last_delivery_date) / (1000 * 60 * 60 * 24);
        if (daysSinceDelivery < 30) score += 10;
        else if (daysSinceDelivery < 90) score += 5;
    }
    
    return Math.max(0, Math.min(100, Math.round(score)));
}

function determineRiskLevel(metrics) {
    if (metrics.total_orders >= 5 && metrics.delivery_success_rate < 30) {
        return 'BLACKLIST';
    }
    if (metrics.delivery_success_rate < 50) {
        return 'HIGH';
    }
    if (metrics.delivery_success_rate < 70) {
        return 'MEDIUM';
    }
    return 'LOW';
}
```

### Task 4: Lead Recycling Evaluation

```javascript
async function evaluateForRecycling(order) {
    const customer = await db.customers.findOne({ id: order.customer_id });
    
    // Skip if customer is blacklisted
    if (customer.risk_level === 'BLACKLIST') {
        return null;
    }
    
    // Skip if customer is in cooldown
    if (customer.recycling_cooldown_until && 
        new Date(customer.recycling_cooldown_until) > new Date()) {
        return null;
    }
    
    // Determine recycle reason and priority
    let recycleReason = null;
    let priorityScore = 50;
    let availableFrom = new Date();
    
    if (order.current_status === 'RETURNED') {
        // Check why it was returned
        const returnReason = order.jnt_raw_data?.return_reason || 'UNKNOWN';
        
        if (['REFUSED', 'CANCELLED_BY_CUSTOMER'].includes(returnReason)) {
            // Customer actively refused - lower priority, longer cooldown
            recycleReason = 'RETURNED_REFUSED';
            priorityScore = 20;
            availableFrom = addDays(new Date(), 14); // 2 week cooldown
        } else if (['UNREACHABLE', 'WRONG_ADDRESS'].includes(returnReason)) {
            // Delivery issue - higher priority, can retry sooner
            recycleReason = 'RETURNED_DELIVERABLE';
            priorityScore = 70;
            availableFrom = addDays(new Date(), 3); // 3 day cooldown
        } else {
            recycleReason = 'RETURNED_OTHER';
            priorityScore = 40;
            availableFrom = addDays(new Date(), 7);
        }
    }
    
    // Adjust priority based on customer score
    priorityScore = Math.round(priorityScore * (customer.customer_score / 50));
    priorityScore = Math.max(1, Math.min(100, priorityScore));
    
    // Check if already in recycling pool
    const existingRecycle = await db.lead_recycling_pool.findOne({
        customer_id: customer.id,
        pool_status: 'AVAILABLE'
    });
    
    if (existingRecycle) {
        // Update existing entry
        await db.lead_recycling_pool.update({ id: existingRecycle.id }, {
            recycle_count: existingRecycle.recycle_count + 1,
            priority_score: Math.min(priorityScore, existingRecycle.priority_score), // Lower if repeated
            source_waybill: order.waybill_number,
            original_outcome: order.current_status,
            updated_at: new Date()
        });
    } else if (recycleReason) {
        // Create new recycling entry
        await db.lead_recycling_pool.create({
            customer_id: customer.id,
            source_waybill: order.waybill_number,
            source_lead_id: order.lead_id,
            original_outcome: order.current_status,
            recycle_reason: recycleReason,
            priority_score: priorityScore,
            available_from: availableFrom,
            expires_at: addDays(availableFrom, 30), // Expires after 30 days
            pool_status: 'AVAILABLE'
        });
    }
}
```

### Task 5: Lead Recycling Assignment

```javascript
async function getRecyclableLeads(agentId, count = 10, filters = {}) {
    const query = {
        pool_status: 'AVAILABLE',
        available_from: { $lte: new Date() },
        expires_at: { $gt: new Date() }
    };
    
    // Apply filters
    if (filters.minPriority) {
        query.priority_score = { $gte: filters.minPriority };
    }
    if (filters.recycleReason) {
        query.recycle_reason = filters.recycleReason;
    }
    
    const leads = await db.lead_recycling_pool
        .find(query)
        .sort({ priority_score: -1 })
        .limit(count)
        .populate('customer_id');
    
    // Mark as assigned
    const leadIds = leads.map(l => l.id);
    await db.lead_recycling_pool.updateMany(
        { id: { $in: leadIds } },
        {
            assigned_to: agentId,
            assigned_at: new Date(),
            pool_status: 'ASSIGNED'
        }
    );
    
    return leads.map(lead => ({
        recycleId: lead.id,
        customer: {
            id: lead.customer_id.id,
            name: lead.customer_id.name_display,
            phone: lead.customer_id.phone_primary,
            address: lead.customer_id.primary_address,
            city: lead.customer_id.city
        },
        history: {
            totalOrders: lead.customer_id.total_orders,
            delivered: lead.customer_id.total_delivered,
            returned: lead.customer_id.total_returned,
            successRate: lead.customer_id.delivery_success_rate,
            customerScore: lead.customer_id.customer_score,
            riskLevel: lead.customer_id.risk_level
        },
        recycleInfo: {
            reason: lead.recycle_reason,
            previousOutcome: lead.original_outcome,
            timesRecycled: lead.recycle_count,
            priority: lead.priority_score
        }
    }));
}
```

### Task 6: Process Recycled Lead Outcome

```javascript
async function processRecycleOutcome(recycleId, outcome, notes = '') {
    const recycle = await db.lead_recycling_pool.findOne({ id: recycleId });
    const customer = await db.customers.findOne({ id: recycle.customer_id });
    
    // Update recycling pool entry
    await db.lead_recycling_pool.update({ id: recycleId }, {
        pool_status: outcome === 'CONVERTED' ? 'CONVERTED' : 'EXHAUSTED',
        processed_at: new Date(),
        processed_outcome: outcome
    });
    
    // Update customer contact info
    await db.customers.update({ id: customer.id }, {
        times_contacted: customer.times_contacted + 1,
        last_contact_date: new Date()
    });
    
    // Handle different outcomes
    switch (outcome) {
        case 'SALE':
        case 'REORDER':
            // Success! Create new lead/order
            const newLead = await createLeadFromRecycle(recycle, customer, outcome);
            
            // Set cooldown (they just ordered, don't contact again soon)
            await db.customers.update({ id: customer.id }, {
                recycling_cooldown_until: addDays(new Date(), 30)
            });
            
            return { success: true, newLeadId: newLead.id };
            
        case 'NO_ANSWER':
            // Put back in pool with lower priority
            if (recycle.recycle_count < 3) {
                await db.lead_recycling_pool.create({
                    customer_id: customer.id,
                    source_waybill: recycle.source_waybill,
                    original_outcome: 'NO_ANSWER',
                    recycle_reason: 'NO_ANSWER_RETRY',
                    recycle_count: recycle.recycle_count + 1,
                    priority_score: Math.max(10, recycle.priority_score - 20),
                    available_from: addDays(new Date(), 1), // Try again tomorrow
                    expires_at: addDays(new Date(), 14),
                    pool_status: 'AVAILABLE'
                });
            }
            return { success: false, action: 'QUEUED_RETRY' };
            
        case 'DECLINED':
        case 'NOT_INTERESTED':
            // Set longer cooldown
            await db.customers.update({ id: customer.id }, {
                recycling_cooldown_until: addDays(new Date(), 90)
            });
            return { success: false, action: 'COOLDOWN_90_DAYS' };
            
        case 'DO_NOT_CALL':
            // Blacklist customer
            await db.customers.update({ id: customer.id }, {
                recycling_eligible: false,
                risk_level: 'BLACKLIST'
            });
            return { success: false, action: 'BLACKLISTED' };
            
        case 'CALLBACK':
            // Schedule callback
            const callbackDate = new Date(notes); // Expecting ISO date string
            await db.lead_recycling_pool.create({
                customer_id: customer.id,
                source_waybill: recycle.source_waybill,
                original_outcome: 'CALLBACK_REQUESTED',
                recycle_reason: 'SCHEDULED_CALLBACK',
                recycle_count: recycle.recycle_count,
                priority_score: 90, // High priority for callbacks
                available_from: callbackDate,
                expires_at: addDays(callbackDate, 2),
                pool_status: 'AVAILABLE'
            });
            return { success: false, action: 'CALLBACK_SCHEDULED' };
            
        default:
            return { success: false, action: 'UNKNOWN_OUTCOME' };
    }
}
```

---

## API Endpoints to Implement

```javascript
// Customer Historical Data
GET  /api/customers/:id                    // Get customer profile with full history
GET  /api/customers/:id/orders             // Get customer order history
GET  /api/customers/:id/timeline           // Get customer interaction timeline
GET  /api/customers/search?phone=&name=    // Search customers

// Lead Recycling Pool
GET  /api/recycling/pool                   // Get available recycling leads
GET  /api/recycling/pool/stats             // Pool statistics
POST /api/recycling/assign                 // Assign leads to agent
POST /api/recycling/:id/outcome            // Record outcome

// Metrics & Reporting
GET  /api/reports/customer-performance     // Customer performance report
GET  /api/reports/recycling-conversion     // Recycling conversion rates
GET  /api/reports/agent-performance        // Agent performance metrics

// J&T Sync
POST /api/sync/jnt/pull                    // Manual J&T data pull
GET  /api/sync/jnt/status                  // Sync status and logs
```

---

## Scheduled Jobs

```javascript
// Every 30 minutes: Sync with J&T
cron.schedule('*/30 * * * *', async () => {
    const pendingWaybills = await db.customer_order_history.find({
        current_status: { $nin: ['DELIVERED', 'RETURNED', 'CANCELLED'] },
        jnt_waybill: { $exists: true }
    }).select('waybill_number');
    
    await syncWithJNT(pendingWaybills.map(w => w.waybill_number));
});

// Every day at 2 AM: Recalculate all customer metrics
cron.schedule('0 2 * * *', async () => {
    const customers = await db.customers.find().select('id');
    for (const customer of customers) {
        await updateCustomerMetrics(customer.id);
    }
});

// Every day at 3 AM: Expire old recycling entries
cron.schedule('0 3 * * *', async () => {
    await db.lead_recycling_pool.updateMany(
        {
            pool_status: 'AVAILABLE',
            expires_at: { $lt: new Date() }
        },
        { pool_status: 'EXPIRED' }
    );
});

// Every day at 4 AM: Release stale assignments
cron.schedule('0 4 * * *', async () => {
    const staleThreshold = new Date(Date.now() - 24 * 60 * 60 * 1000); // 24 hours
    
    await db.lead_recycling_pool.updateMany(
        {
            pool_status: 'ASSIGNED',
            assigned_at: { $lt: staleThreshold }
        },
        {
            pool_status: 'AVAILABLE',
            assigned_to: null,
            assigned_at: null
        }
    );
});
```

---

## UI Components Needed

### 1. Customer Profile Page
- Header with customer score, risk level badge
- Quick stats cards (total orders, delivered, returned, success rate)
- Order history timeline
- Lead interaction history
- Recycling status indicator

### 2. Recycling Pool Dashboard
- Available leads queue with priority sorting
- Filter by recycle reason, priority, risk level
- Batch assignment to agents
- Quick action buttons for outcomes

### 3. Agent Workstation
- Current assigned recycled leads
- Customer quick view with history
- One-click outcome recording
- Callback scheduling modal

### 4. Reports Dashboard
- Customer cohort analysis
- Recycling funnel metrics
- Agent conversion comparison
- Trend charts

---

## Risk Level Color Coding

```javascript
const RISK_COLORS = {
    LOW: '#22c55e',       // Green
    MEDIUM: '#eab308',    // Yellow
    HIGH: '#f97316',      // Orange
    BLACKLIST: '#ef4444'  // Red
};

const CUSTOMER_SCORE_COLORS = {
    '0-25': '#ef4444',    // Red
    '26-50': '#f97316',   // Orange
    '51-75': '#eab308',   // Yellow
    '76-100': '#22c55e'   // Green
};
```

---

## Agent Decision Rules Summary

| Scenario | Action | Priority | Cooldown |
|----------|--------|----------|----------|
| Returned - Customer Refused | Add to pool | 20 | 14 days |
| Returned - Delivery Issue | Add to pool | 70 | 3 days |
| No Answer (1st-2nd time) | Retry | Current - 20 | 1 day |
| No Answer (3rd time) | Remove | N/A | N/A |
| Sale/Reorder | Success | N/A | 30 days |
| Declined | Remove | N/A | 90 days |
| Do Not Call | Blacklist | N/A | Permanent |
| Callback Requested | Schedule | 90 | Until date |

---

## Implementation Priority

1. **Phase 1**: Customer table + basic deduplication
2. **Phase 2**: Order history + J&T sync integration
3. **Phase 3**: Metrics calculation + customer scoring
4. **Phase 4**: Recycling pool + assignment system
5. **Phase 5**: Outcome processing + automated rules
6. **Phase 6**: Reports + analytics dashboards
