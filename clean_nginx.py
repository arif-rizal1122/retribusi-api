import re

with open('mpad-backend-temp.conf', 'r') as f:
    content = f.read()

# We want to remove blocks matching certain domains.
domains_to_remove = [
    'dev.sipanda.online',
    'admin-dev.sipanda.online',
    'petugas-dev.sipanda.online',
    'api.sipanda.online',
    'www.sipanda.online',
    'sipanda.online',
    'admin.sipanda.online',
    'petugas.sipanda.online'
]

def remove_blocks(text, domains):
    # This matches a 'server {' block and grabs everything inside it until the closing '}'
    # But because blocks can have nested {} (like location blocks), we need a balanced {} matcher.
    
    # A simpler way: split by "server {" and check if the block contains the domain.
    # We must be careful about nested braces.
    
    parts = text.split("server {")
    new_parts = [parts[0]] # Everything before the first server block
    
    for part in parts[1:]:
        # check if this block is for any of the domains
        remove = False
        for d in domains:
            # We look for "server_name ... d;"
            if f"server_name {d}" in part or f"server_name {d};" in part or f"host = {d}" in part:
                remove = True
                break
        if remove:
            # We need to find the matching closing brace for this block and keep the rest.
            open_braces = 1
            idx = 0
            while open_braces > 0 and idx < len(part):
                if part[idx] == '{':
                    open_braces += 1
                elif part[idx] == '}':
                    open_braces -= 1
                idx += 1
            rest_of_text = part[idx:]
            new_parts.append(rest_of_text)
        else:
            new_parts.append("server {" + part)
            
    return "".join(new_parts)

cleaned = remove_blocks(content, domains_to_remove)
with open('mpad-backend-clean.conf', 'w') as f:
    f.write(cleaned)
