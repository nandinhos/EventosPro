import re

source_file = '/home/nandodev/projects/EventosPro/doc-project/bkp/dev_eventos_backup_2026-03-22_23-54.sql'

def count_contracts():
    with open(source_file, 'r', encoding='utf-8') as f:
        content = f.read()
        
    match = re.search(r'INSERT INTO `contracts` VALUES (.*?);', content, re.DOTALL)
    if not match:
        print("Contracts table not found or empty.")
        return
    
    values_raw = match.group(1)
    
    # Split by ),( but this is naive if strings contain it
    # Better: count actual valid rows
    rows = 0
    in_quote = False
    in_row = False
    i = 0
    while i < len(values_raw):
        c = values_raw[i]
        if c == "'" and (i == 0 or values_raw[i-1] != '\\'):
            in_quote = not in_quote
        elif not in_quote:
            if c == '(':
                in_row = True
            elif c == ')':
                if in_row:
                    rows += 1
                    in_row = False
        i += 1
    
    print(f"Total contracts rows in source: {rows}")

if __name__ == '__main__':
    count_contracts()
