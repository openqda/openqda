#!/bin/bash

# Function to read a value from the .env file
function get_env_value() {
    grep -w $1 .env | cut -d '=' -f2
}

# Get the host value from the .env file
HOSTNAME=$(get_env_value "APP_HOST")

# Start the WebSocket server with the value from .env
php artisan reverb:start --host="0.0.0.0" --port=8080 --hostname="$HOSTNAME" &
WEBSOCKET_PID=$!

# Start the queue worker
php artisan queue:work --queue=conversion,default &
QUEUE_WORKER_PID=$!

# Function to kill the processes when the script exits
cleanup() {
    echo "Stopping WebSocket server..."
    kill $WEBSOCKET_PID
    echo "Stopping Queue worker..."
    kill $QUEUE_WORKER_PID
}

# Trap the exit signal to run the cleanup function
trap cleanup EXIT

# Wait for the background processes to finish
wait $WEBSOCKET_PID
wait $QUEUE_WORKER_PID
