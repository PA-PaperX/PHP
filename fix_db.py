import sys

try:
    with open('database/backup_utf8.sql', 'r', encoding='utf-8-sig') as f:
        text = f.read()
    
    # Encode back to cp437 to get the original raw bytes
    # Use errors='replace' or 'ignore' if some chars don't map, but they should all map
    raw_bytes = text.encode('cp437')
    
    # Now write the raw bytes as the new SQL file
    with open('database/backup_thai.sql', 'wb') as f:
        f.write(raw_bytes)
        
    print("Success! Created backup_thai.sql")
except Exception as e:
    print(f"Error: {e}")
