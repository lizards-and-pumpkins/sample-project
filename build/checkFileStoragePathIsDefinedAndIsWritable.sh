#!/bin/bash

if [ -z $LP_FILE_STORAGE_BASE_PATH ]; then
  echo "LP_FILE_STORAGE_BASE_PATH is not sen in environment."
  exit 1
fi

if [ ! -w $LP_FILE_STORAGE_BASE_PATH ]; then
  echo "$LP_FILE_STORAGE_BASE_PATH is not writable."
  exit 1
fi
