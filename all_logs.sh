#!/bin/bash

ADDR="100.101.223.42"
USER="anthony"
FILE="/var/log/it490/system.log"

ssh -t ${USER}@${ADDR} "sudo tail -f ${FILE}"

