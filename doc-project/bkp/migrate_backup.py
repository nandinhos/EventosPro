import re
import sys
import os
from datetime import datetime

source_file = '/home/nandodev/projects/EventosPro/doc-project/bkp/dev_eventos_backup_2026-03-22_23-54.sql'
target_data_file = '/home/nandodev/projects/EventosPro/doc-project/bkp/migrated_data.sql'

def parse_values(line):
    start = line.find('VALUES (')
    if start == -1: return []
    content = line[start + 7:].strip().rstrip(';')
    
    # Placeholder for escaped quotes
    placeholder = "___ESCAPED_QUOTE___"
    processed_content = content.replace("\\'", placeholder)
    
    # Matches individual (row1), (row2), ...
    matches = re.findall(r'\((.*?)\)(?:,|$)', processed_content)
    
    records = []
    for m in matches:
        # Split by comma but ignore commas inside quotes
        parts = re.split(r",(?=(?:[^']*'[^']*')*[^']*$)", m)
        parts = [p.strip().strip("'") if p.strip() != 'NULL' else None for p in parts]
        
        # Restore escaped quotes in each part
        final_parts = []
        for p in parts:
            if p is not None:
                final_parts.append(p.replace(placeholder, "'"))
            else:
                final_parts.append(None)
        records.append(final_parts)
    return records

def migrate():
    data = {
        'artists': [],
        'bookers': [],
        'clients': [],
        'contracts': []
    }

    current_now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    print(f"Reading source file: {source_file}")
    with open(source_file, 'r', encoding='utf-8') as f:
        current_table = None
        buffered_line = ""
        for line in f:
            if line.startswith('INSERT INTO'):
                match = re.search(r'INSERT INTO `(\w+)`', line)
                if match:
                    current_table = match.group(1)
                buffered_line = line.strip()
            elif current_table and buffered_line:
                buffered_line += " " + line.strip()
            
            if buffered_line.endswith(';'):
                if current_table == 'artists':
                    data['artists'].extend(parse_values(buffered_line))
                elif current_table == 'bookers':
                    data['bookers'].extend(parse_values(buffered_line))
                elif current_table == 'clients':
                    data['clients'].extend(parse_values(buffered_line))
                elif current_table == 'contracts':
                    data['contracts'].extend(parse_values(buffered_line))
                buffered_line = ""
                current_table = None

    artists_inserts = []
    bookers_inserts = []
    service_takers_inserts = []
    gigs_inserts = []

    print("Processing artists...")
    for r in data['artists']:
        if not r or len(r) < 2: continue
        name = r[1].replace("'", "''") if r[1] else ""
        stage_name = r[2].replace("'", "''") if len(r) > 2 and r[2] else ""
        contact = f"Stage Name: {stage_name}" if stage_name else ""
        created_at = r[4] if len(r) > 4 and r[4] else current_now
        updated_at = r[5] if len(r) > 5 and r[5] else current_now
        artists_inserts.append(f"INSERT INTO artists (id, name, contact_info, created_at, updated_at, deleted_at) VALUES ({r[0]}, '{name}', '{contact}', '{created_at}', '{updated_at}', NULL);")

    print("Processing bookers...")
    for r in data['bookers']:
        if not r or len(r) < 2: continue
        name = r[1].replace("'", "''") if r[1] else ""
        email = r[2] if len(r) > 2 and r[2] else ""
        phone = r[3] if len(r) > 3 and r[3] else ""
        rate = r[4] if len(r) > 4 and r[4] else "20.00"
        contact = f"Email: {email} | Phone: {phone}"
        contact = contact.replace("'", "''")
        created_at = r[6] if len(r) > 6 and r[6] else current_now
        updated_at = r[7] if len(r) > 7 and r[7] else current_now
        bookers_inserts.append(f"INSERT INTO bookers (id, name, default_commission_rate, contact_info, created_at, updated_at, deleted_at) VALUES ({r[0]}, '{name}', {rate}, '{contact}', '{created_at}', '{updated_at}', NULL);")

    print("Processing service takers...")
    for r in data['clients']:
        if not r or len(r) < 2: continue
        org = r[1].replace("'", "''") if r[1] else ""
        doc = r[2].replace("'", "''") if len(r) > 2 and r[2] else ""
        contact_name = r[3].replace("'", "''") if len(r) > 3 and r[3] else ""
        email = r[4].replace("'", "''") if len(r) > 4 and r[4] else ""
        phone = r[5].replace("'", "''") if len(r) > 5 and r[5] else ""
        address = r[6].replace("'", "''") if len(r) > 6 and r[6] else ""
        
        created_at = r[8] if len(r) > 8 and r[8] else current_now
        updated_at = r[9] if len(r) > 9 and r[9] else current_now
        
        service_takers_inserts.append(f"INSERT INTO service_takers (id, organization, document, contact, email, phone, street, is_international, created_at, updated_at, deleted_at) VALUES ({r[0]}, '{org}', '{doc}', '{contact_name}', '{email}', '{phone}', '{address}', 0, '{created_at}', '{updated_at}', NULL);")

    print("Processing gigs...")
    for r in data['contracts']:
        if not r or len(r) < 20: continue
        try:
            val = float(r[16]) if r[16] else 0.0
            comm = float(r[17]) if r[17] else 0.0
            rate = (comm / val * 100) if val > 0 else 20.0
            comm_val = comm
            
            # Location details
            event = r[10] if len(r) > 10 and r[10] else ""
            venue = r[11] if len(r) > 11 and r[11] else ""
            city = r[12] if len(r) > 12 and r[12] else ""
            state = r[13] if len(r) > 13 and r[13] else ""
            
            loc_parts = []
            if venue: loc_parts.append(venue)
            if event: loc_parts.append(event)
            loc = " - ".join(loc_parts)
            if city or state:
                geo = "/".join(filter(None, [city, state]))
                loc += f" ({geo})"
            loc = loc.replace("'", "''")
            
            notes = (r[22] or "").replace("'", "''") if len(r) > 22 else ""
            contract_num = (r[3] or "").replace("'", "''") if len(r) > 3 else ""
            
            artist_id = r[5]
            booker_id = r[6] if len(r) > 6 and r[6] is not None and r[6] != 'NULL' else 'NULL'
            client_id = r[7] if len(r) > 7 and r[7] is not None and r[7] != 'NULL' else 'NULL'
            
            status = r[19] if len(r) > 19 and r[19] else 'confirmed'
            currency = r[18] if len(r) > 18 and r[18] else 'BRL'
            
            created_at = r[23] if len(r) > 23 and r[23] else current_now
            updated_at = r[24] if len(r) > 24 and r[24] else current_now
            
            gigs_inserts.append(f"INSERT INTO gigs (id, artist_id, booker_id, service_taker_id, contract_number, contract_date, gig_date, location_event_details, cache_value, currency, agency_commission_type, agency_commission_rate, agency_commission_value, booker_commission_type, booker_commission_rate, booker_commission_value, liquid_commission_value, contract_status, payment_status, artist_payment_status, booker_payment_status, notes, created_at, updated_at, deleted_at) VALUES ({r[0]}, {artist_id}, {booker_id}, {client_id}, '{contract_num}', '{r[8]}', '{r[9]}', '{loc}', {val}, '{currency}', 'percent', {rate}, {comm_val}, 'percent', 0.0, 0.0, {comm_val}, '{status}', 'a_vencer', 'pendente', 'pendente', '{notes}', '{created_at}', '{updated_at}', NULL);")
        except Exception as e:
            pass

    # Prepare output grouping by table
    final_output = []
    final_output.append("SET FOREIGN_KEY_CHECKS = 0;")
    
    final_output.append("\n-- Data for table artists")
    final_output.append("TRUNCATE TABLE artists;")
    final_output.extend(artists_inserts)
    
    final_output.append("\n-- Data for table bookers")
    final_output.append("TRUNCATE TABLE bookers;")
    final_output.extend(bookers_inserts)
    
    final_output.append("\n-- Data for table service_takers")
    final_output.append("TRUNCATE TABLE service_takers;")
    final_output.extend(service_takers_inserts)
    
    final_output.append("\n-- Data for table gigs")
    final_output.append("TRUNCATE TABLE gigs;")
    final_output.extend(gigs_inserts)
    
    final_output.append("\nSET FOREIGN_KEY_CHECKS = 1;")

    print(f"Writing output to {target_data_file}")
    with open(target_data_file, 'w', encoding='utf-8') as f:
        f.write('\n'.join(final_output))

if __name__ == '__main__':
    migrate()
    print(f"Data migration completed. Output saved to migrated_data.sql")
