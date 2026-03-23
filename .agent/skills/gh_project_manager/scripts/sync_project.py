import json
import subprocess
import re

def run_gh_command(cmd):
    result = subprocess.run(cmd, capture_output=True, text=True)
    if result.returncode != 0:
        print(f"Error for command {' '.join(cmd)}:\nSTDOUT: {result.stdout.strip()}\nSTDERR: {result.stderr.strip()}")
        return None
    return result.stdout.strip()

def main():
    print("Fetching current project items...")
    project_number = 3
    owner = "muhdanfyan"
    project_id = "PVT_kwHOAPm_Ts4BPIUY"
    
    items_json = run_gh_command(["gh", "project", "item-list", str(project_number), "--owner", owner, "--format", "json"])
    if not items_json:
        return
    
    data = json.loads(items_json)
    items = data.get('items', [])
    
    for item in items:
        item_id = item.get('id')
        content = item.get('content', {})
        item_type = content.get('type') or item.get('type')
        
        draft_id = content.get('id') if item_type == "DraftIssue" else None
        
        title = item.get('title') or content.get('title') or ''
        body = content.get('body') or item.get('body') or ''
        
        # Replacement Logic:
        # 1. Ensure domains are sipanda.online
        new_title = re.sub(r'mpad\.online', 'sipanda.online', title, flags=re.IGNORECASE)
        new_body = re.sub(r'mpad\.online', 'sipanda.online', body, flags=re.IGNORECASE)
        
        # 2. Replace SIPANDA (the name) with Mpad/MPAD
        # Negative lookahead for .online
        name_pattern = r'\bSIPANDA\b(?!(\.online))'
        new_title = re.sub(name_pattern, 'Mpad', new_title, flags=re.IGNORECASE)
        new_body = re.sub(name_pattern, 'Mpad', new_body, flags=re.IGNORECASE)
        
        if new_title != title or new_body != body:
            print(f"Updating item {item_id} ({item_type}): {title}")
            
            if item_type == "DraftIssue" and draft_id:
                # Always provide title for DraftIssues to avoid "Title can't be blank" error
                cmd = [
                    "gh", "project", "item-edit",
                    "--id", draft_id,
                    "--project-id", project_id,
                    "--title", new_title
                ]
                if new_body != body:
                    cmd.extend(["--body", new_body])
                run_gh_command(cmd)
            elif item_type == "Issue":
                repo = content.get('repository')
                issue_number = content.get('number')
                if repo and issue_number:
                    cmd = [
                        "gh", "issue", "edit",
                        str(issue_number),
                        "--repo", repo
                    ]
                    if new_title != title:
                        cmd.extend(["--title", new_title])
                    if new_body != body:
                        cmd.extend(["--body", new_body])
                    run_gh_command(cmd)

if __name__ == "__main__":
    main()
