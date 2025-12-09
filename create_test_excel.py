import pandas as pd

data = {
    'Waybill Number': ['WB-TEST-001', 'WB-TEST-002', 'WB-TEST-003'],
    'Sender Name': ['Test Sender 1', 'Test Sender 2', 'Test Sender 3'],
    'Sender Address': ['Address 1', 'Address 2', 'Address 3'],
    'Sender Phone': ['111', '222', '333'],
    'Receiver Name': ['Receiver 1', 'Receiver 2', 'Receiver 3'],
    'Receiver Address': ['Dest Address 1', 'Dest Address 2', 'Dest Address 3'],
    'Receiver Phone': ['999', '888', '777'],
    'Destination': ['City A', 'City B', 'City C'],
    'Weight': [1.5, 2.5, 3.5],
    'Quantity': [1, 2, 3],
    'Service Type': ['Standard', 'Express', 'Standard'],
    'COD Amount': [0, 100, 0],
    'Remarks': ['Test 1', 'Test 2', 'Test 3']
}

df = pd.DataFrame(data)
df.to_excel('test_waybills.xlsx', index=False, header=False)
print("Created test_waybills.xlsx")
