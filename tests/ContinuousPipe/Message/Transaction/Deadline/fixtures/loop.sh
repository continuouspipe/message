#!/bin/sh
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

if [ "$1" != "continuouspipe:message:extend-deadline" ]; then
    echo "First command should be 'continuouspipe:message:extend-deadline'"
    exit 1
fi

if [ -z "$2" ]; then
    echo "Connection name is required"
    exit 1
fi

echo > $DIR/$2.trace
while true; do
    echo . >> $DIR/$2.trace

    sleep 1
done
