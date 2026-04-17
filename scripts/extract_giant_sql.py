#!/usr/bin/env python3
import sys

# Script to extract a specific table's INSERT statement from a giant SQL file
# Usage: python3 extract.py <filename> <tablename>

filename = sys.argv[1]
tablename = sys.argv[2]
search_str = f"INSERT INTO `{tablename}`"

with open(filename, 'r', encoding='utf-8', errors='ignore') as f:
    for line in f:
        if search_str in line:
            # We found the line. Change to INSERT IGNORE
            fix_line = line.replace("INSERT INTO", "INSERT IGNORE INTO")
            print(fix_line)
            # Dumps usually have one INSERT per table for users
            break
