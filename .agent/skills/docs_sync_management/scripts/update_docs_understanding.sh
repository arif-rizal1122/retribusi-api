#!/bin/bash

# Configuration
API_ROOT="/Users/pondokit/Herd/retribusi-api"
MAPPING_FILE="$API_ROOT/docs/98_pemetaan_fungsi/MAP_FUNGSIONAL_DOKUMEN.md"
MEGA_DOC="$API_ROOT/docs/99_umum_system/MEGA_DOCUMENTATION.md"

echo "🧠 Updating Documentation Understanding..."

# 1. Update MEGA_DOCUMENTATION.md
echo "   - Refreshing Mega Documentation..."
# Reuse the logic of merging files (limited to retribusi-api for speed)
echo "# MEGA DOCUMENTATION" > "$MEGA_DOC"
find "$API_ROOT/docs" -name "*.md" -not -name "MEGA_DOCUMENTATION.md" -not -name "MAP_FUNGSIONAL_DOKUMEN.md" | while read -r file; do
    echo "## File: ${file#$API_ROOT/}" >> "$MEGA_DOC"
    echo "---" >> "$MEGA_DOC"
    cat "$file" >> "$MEGA_DOC"
    echo -e "\n\n---\n" >> "$MEGA_DOC"
done

# 2. Update MAP_FUNGSIONAL_DOKUMEN.md
echo "   - Updating Functional Mapping..."
# For simplicity in this script, we ensure the mapping file exists and has its structure.
# Complex auto-categorization can be added here.
if [ ! -f "$MAPPING_FILE" ]; then
    echo "Creating Master Mapping file..."
    # (Base template would go here, currently we assume it exists from previous manual creation)
fi

echo "✅ Document Understanding Updated."
echo "   - Master Mapping: $MAPPING_FILE"
echo "   - Mega Doc: $MEGA_DOC"
