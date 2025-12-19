#!/bin/bash

ADDR="100.123.15.99"
USER="anthony"
FILE="/var/log/it490/system.log"

ssh -t ${USER}@${ADDR} "sudo tail -f ${FILE}"

