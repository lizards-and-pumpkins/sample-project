#!/usr/bin/env bash

REPLACEMENT_COMMAND=vendor/lizards-and-pumpkins/catalog/bin/consumerSupervisor.sh

echo NOTE:
echo This script has been deprecated!
echo Please use the following command instead:
echo $REPLACEMENT_COMMAND

echo -n .
sleep 1
echo -n .
sleep 1
echo -n .
sleep 1
echo

$(dirname $0)/../../../$REPLACEMENT_COMMAND "$@"
