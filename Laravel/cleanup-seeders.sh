#!/bin/bash

# ============================================
# CLEANUP SCRIPT - Remove obsolete e-commerce seeders
# ============================================
# 
# These seeders have been consolidated into EcommerceSeeder.php
# Run this script from the Laravel root directory.
#
# Usage: bash cleanup-seeders.sh
# Or make executable: chmod +x cleanup-seeders.sh && ./cleanup-seeders.sh

echo "🧹 Cleaning up obsolete e-commerce seeders..."
echo ""

# List of files to remove
FILES_TO_REMOVE=(
    "database/seeders/StoreSeeder.php"
    "database/seeders/CartStatusSeeder.php"
    "database/seeders/OrderStatusSeeder.php"
    "database/seeders/FilterSeeder.php"
    "database/seeders/CategorySeeder.php"
    "database/seeders/ArticleSeeder.php"
    "database/seeders/StockSeeder.php"
    "database/seeders/PaymentModeSeeder.php"
)

# Counter for removed files
removed=0
not_found=0

for file in "${FILES_TO_REMOVE[@]}"; do
    if [ -f "$file" ]; then
        echo "  🗑️  Removing: $file"
        rm "$file"
        ((removed++))
    else
        echo "  ⚠️  Not found: $file (already removed?)"
        ((not_found++))
    fi
done

echo ""
echo "✅ Cleanup complete!"
echo "   - Files removed: $removed"
echo "   - Files not found: $not_found"
echo ""
echo "📋 Next steps:"
echo "   1. Copy EcommerceSeeder.php to database/seeders/"
echo "   2. Copy DatabaseSeeder.php to database/seeders/"
echo "   3. Run: php artisan db:seed --class=EcommerceSeeder"
echo ""
