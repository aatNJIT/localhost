#!/bin/bash

ADDR="192.168.56.103"
USER="anthony"
FILE="/var/log/it490/system.log"

ssh -t ${USER}@${ADDR} "sudo tail -f ${FILE}"

