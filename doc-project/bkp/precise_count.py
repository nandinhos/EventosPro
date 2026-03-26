import re

source_file = '/home/nandodev/projects/EventosPro/doc-project/bkp/dev_eventos_backup_2026-03-22_23-54.sql'

def count_all_rows(table_name):
    with open(source_file, 'r', encoding='utf-8') as f:
        in_table = False
        content = ""
        for line in f:
            if f"INSERT INTO `{table_name}`" in line:
                in_table = True
                content = line
            elif in_table:
                content += line
                if ';' in line:
                    break
        
        if not content:
            print(f"Table {table_name} not found.")
            return
        
        # Simple count by counting occurrences of ),( and the final );
        count = content.count('),(') + 1
        print(f"Table {table_name}: ~{count} rows")
        
        # More precise: match (id, ...)
        # We assume ID is the first field and it's numeric
        matches = re.findall(r'\((\d+),', content)
        print(f"Table {table_name}: {len(matches)} rows by ID match")
        
        if len(matches) > 0:
            print(f"Min ID: {min(map(int, matches))}, Max ID: {max(map(int, matches))}")

if __name__ == '__main__':
    count_all_rows('contracts')
    count_all_rows('receivables')
    count_all_rows('payables')
