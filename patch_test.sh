#!/bin/bash
# Modify test_production_regression.sh to add curl retries
sed -i 's/TOKEN=$(curl/TOKEN=$(curl --retry 5 --retry-delay 2 --retry-connrefused/g' testing/test_production_regression.sh
sed -i 's/STATUS=$(curl/STATUS=$(curl --retry 5 --retry-delay 2 --retry-connrefused/g' testing/test_production_regression.sh
