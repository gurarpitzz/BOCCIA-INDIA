import mysql.connector

emails = ['gurarpit.sml@gmail.com', 'mehardeep.sim@gmail.com']

try:
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="boccia_india"
    )
    cursor = conn.cursor(dictionary=True)

    print("--- ATHLETES TABLE ---")
    for email in emails:
        cursor.execute("SELECT id, full_name, email, deleted_at FROM athletes WHERE email = %s", (email,))
        row = cursor.fetchone()
        if row:
            print(row)
        else:
            print(f"{email} not found in athletes")

    print("\n--- ATHLETE APPLICATIONS TABLE ---")
    for email in emails:
        cursor.execute("SELECT id, full_name, email, status, possible_duplicate FROM athlete_applications WHERE email = %s", (email,))
        rows = cursor.fetchall()
        if rows:
            print(rows)
        else:
            print(f"{email} not found in athlete_applications")

    print("\n--- OFFICIALS TABLE ---")
    for email in emails:
        cursor.execute("SELECT id, name, email, deleted_at FROM officials WHERE email = %s", (email,))
        row = cursor.fetchone()
        if row:
            print(row)
        else:
            print(f"{email} not found in officials")

    print("\n--- OFFICIAL APPLICATIONS TABLE ---")
    for email in emails:
        cursor.execute("SELECT id, name, email, status FROM official_applications WHERE email = %s", (email,))
        rows = cursor.fetchall()
        if rows:
            print(rows)
        else:
            print(f"{email} not found in official_applications")

    cursor.close()
    conn.close()

except Exception as e:
    print(f"Error: {e}")
