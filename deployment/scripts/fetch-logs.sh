#!/bin/bash
# Fetch Laravel Logs
# Run this on the local machine to fetch logs from the app server

SERVER_IP="192.168.120.33"
SSH_USER="it-admin"

echo "Fetching last 200 lines of laravel.log from $SERVER_IP..."

ssh $SSH_USER@$SERVER_IP "tail -n 200 /var/www/waybill/storage/logs/laravel.log" > laravel_error.log

echo "Logs saved to laravel_error.log"
echo "--- Last 50 lines ---"
tail -n 50 laravel_error.log
