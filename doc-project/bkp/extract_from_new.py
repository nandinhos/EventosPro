import re
import os

source_file = '/home/nandodev/projects/EventosPro/doc-project/bkp/eventospro-2026-03-25-192845.sql'
target_data_file = '/home/nandodev/projects/EventosPro/doc-project/bkp/migrated_data.sql'

def extract_table_data(content, table_name):
    print(f"Extracting data for {table_name}...")
    # Semicolon followed by newline is a safer delimiter for mysqldump inserts
    # We look for INSERT INTO `table` VALUES ( then any content until ); at the end of a line
    pattern = rf"INSERT INTO `{table_name}` VALUES (.*?);\n"
    match = re.search(pattern, content, re.DOTALL)
    if match:
        return f"TRUNCATE TABLE `{table_name}`;\nINSERT INTO `{table_name}` VALUES {match.group(1)};"
    return f"-- No data found for {table_name}"

def main():
    if not os.path.exists(source_file):
        print(f"Source file not found: {source_file}")
        return

    with open(source_file, 'r', encoding='utf-8') as f:
        content = f.read()

    # If the file doesn't end with a newline, add one for the regex to match the last insert
    if not content.endswith('\n'):
        content += '\n'

    tables = [
        'artists',
        'bookers',
        'service_takers',
        'gigs',
        'payments',
        'gig_costs',
        'settlements',
        'cost_centers',
        'debit_notes'
    ]

    output = ["SET FOREIGN_KEY_CHECKS = 0;"]
    
    for table in tables:
        output.append(f"\n-- Data for table {table}")
        output.append(extract_table_data(content, table))
        
    output.append("\nSET FOREIGN_KEY_CHECKS = 1;")

    print(f"Writing everything to {target_data_file}")
    with open(target_data_file, 'w', encoding='utf-8') as f:
        f.write('\n'.join(output))

if __name__ == '__main__':
    main()
    print("Extraction completed.")
