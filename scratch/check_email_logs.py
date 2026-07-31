import mysql.connector

try:
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="boccia_india"
    )
    cursor = conn.cursor(dictionary=True)

    print("--- RECENT EMAIL LOGS ---")
    cursor.execute("SELECT id, recipient, subject, status, response_code, response_body, attempts, sent_at FROM email_logs ORDER BY id DESC LIMIT 5")
    rows = cursor.fetchall()
    for row in rows:
        print(row)

    cursor.close()
    conn.close()

except Exception as e:
    print(f"Error: {e}")
