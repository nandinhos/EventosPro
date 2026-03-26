import re

def merge_backups(schema_file, data_file, output_file):
    with open(schema_file, 'r', encoding='utf-8') as f:
        schema_content = f.read()
    
    with open(data_file, 'r', encoding='utf-8') as f:
        data_content = f.read()

    # Split data content into individual table inserts
    # We look for -- Data for table followed by name
    table_data = {}
    current_table = None
    
    for line in data_content.splitlines():
        if "-- Data for table" in line:
            # Match both "-- Data for table artists" and "-- Data for table `artists`"
            m = re.search(r'-- Data for table [`]?(\w+)[`]?', line)
            if m:
                current_table = m.group(1)
                table_data[current_table] = []
                print(f"Found data for table: {current_table}")
        elif current_table and ("INSERT INTO" in line or "TRUNCATE" in line):
            table_data[current_table].append(line)

    print(f"Total tables with data: {list(table_data.keys())}")

    # Now process schema content and inject data
    output_lines = []
    lines = schema_content.splitlines()
    i = 0
    while i < len(lines):
        line = lines[i]
        output_lines.append(line)
        
        # Look for the end of a CREATE TABLE statement for our tables
        if "CREATE TABLE" in line:
            m = re.search(r'`(\w+)`', line)
            if m:
                table_name = m.group(1)
                print(f"Found CREATE TABLE in schema: {table_name}")
                if table_name in table_data:
                    print(f"Match found for {table_name}! Injecting data...")
                    # Find the end of this CREATE TABLE (finding the ;)
                    found_end = False
                    while i < len(lines) and not found_end:
                        if lines[i].strip().endswith(");") or lines[i].strip().endswith(") ENGINE") or "ENGINE =" in lines[i]:
                             found_end = True
                        
                        if not found_end:
                            i += 1
                            output_lines.append(lines[i])
                    
                    # Add data
                    output_lines.append("")
                    output_lines.append(f"-- Data for table `{table_name}`")
                    for d_line in table_data[table_name]:
                        output_lines.append(d_line)
                    output_lines.append("")
        i += 1

    with open(output_file, 'w', encoding='utf-8') as f:
        f.write("\n".join(output_lines))

if __name__ == "__main__":
    schema = "backup-eventosprofssdevcombr_db-2026-03-25-182520.sql"
    data = "migrated_data.sql"
    output = "vps_restoration_full.sql"
    merge_backups(schema, data, output)
    print(f"Successfully merged into {output}")
