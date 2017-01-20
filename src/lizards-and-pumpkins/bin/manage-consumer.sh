#!/usr/bin/env bash

function init_vars() {
    dir="$(dirname $0)"
    supervisor="$dir/consumerSupervisor.sh"
    send_shutdown_message="$dir/shutdownConsumerProcess.php"
    get_input_args "$@"
    build_pid_list
}

function get_input_args() {
    case "$1" in
        c|command|commandConsumer|commandConsumer.php)
            worker="commandConsumer.php"
            ;;
        "e"|"event"|"eventConsumer"|"eventConsumer.php")
            worker="eventConsumer.php"
            ;;
        *)
            echo "Invalid consumer"
            usage
            ;;
    esac
    
    case "$2" in
        "start")
            action="start"
            ;;
        "stop")
            action="stop"
            ;;
        "stop-all")
            action="stop-all"
            ;;
        *)
            echo "Invalid action"
            usage
            ;;
    esac
}

function build_pid_list()
{
    pids=" "$(get_pids_for_worker ${worker})
}

function get_pids_for_worker()
{
    local name=$1
    echo $(ps x|grep $name|grep "$supervisor"|grep -v 'grep '|awk '{ print $1 }')
}

function start_consumer()
{
    "$supervisor" "$dir/${worker}" &
}

function stop_consumer()
{
    local supervisor_pid="${pids##* }"
    if [ ! -z ${supervisor_pid} ]; then
        shutdown_worker ${worker} ${supervisor_pid} 
        pids="${pids% *}"
    fi
}

function shutdown_worker()
{
    local type=$1
    local supervisor_pid=$2
    
    kill -TERM ${supervisor_pid}
    send_shutdown_message ${type} ${supervisor_pid}
}

function send_shutdown_message
{
    local type="${1%Consumer.php}"
    local consumer_pid=$(pgrep -P $2 php)
    
    if [ ! -z ${consumer_pid} ]; then
        ${send_shutdown_message} --quiet ${type} ${consumer_pid}
    fi
}

function usage()
{
    echo "Usage:"
    echo "$0 (event|command) (start|stop|stop-all)"
    exit 2;
}

init_vars "$@"

case "$action" in
    "start")
        start_consumer
        ;;
    "stop")
        stop_consumer
        ;;
    "stop-all")
        for pid in $pids; do
            shutdown_worker "$worker" "$pid"
        done
        ;;
esac
