# Beer stripes setup

The beer tracker uses a separate MySQL/MariaDB database. It reads the residents from the existing cleaning-roster database and synchronizes a copy into the beer database whenever a beer page is opened. Removed residents are marked inactive there; their stripe history is retained.

## Database setup

1. Create a MySQL/MariaDB database and a database user on the hosting site. The user needs permission to create and change tables in that database.
2. Select the new beer database in the hosting database manager and run [`beer-schema.sql`](./beer-schema.sql) once.
3. On the server, add a `beer_database` section to the existing `config.local.php`. Keep the existing `database` settings for the cleaning-roster database unchanged:

   ```php
   'beer_database' => array(
       'host' => 'your-database-host',
       'username' => 'your-beer-database-user',
       'password' => 'your-beer-database-password',
       'name' => 'your-beer-database-name',
       'charset' => 'utf8mb4'
   ),
   ```

   Do not commit `config.local.php` or share its password. The example connection settings are also shown in [`config.example.php`](./config.example.php).
4. Open `bier.php` while signed in to the site. The people list is copied from the existing roster automatically.
5. Sign in with the existing admin account and open **Bierbeheer**. Add a drink with its initial stock quantity and the total price of that batch.

For an existing beer database, run this migration once before using the updated pages:

```sql
ALTER TABLE beer_stock_batches
	ADD COLUMN paid_by_person_id BIGINT UNSIGNED NULL AFTER total_price_cents,
	ADD KEY batch_paid_by (paid_by_person_id),
	ADD CONSTRAINT beer_batches_paid_by_fk FOREIGN KEY (paid_by_person_id)
		REFERENCES beer_people (person_id) ON DELETE SET NULL ON UPDATE RESTRICT;
```

Previous batches remain without an assigned payer because that information was not recorded before this change. New batches require selecting the person who paid.

## How it works

- Any site user can add a stripe from the table on [`bier.php`](./bier.php). The table displays each active resident as a column and each active drink as a row.
- A stripe is only accepted when there is stock. It consumes one item from the oldest remaining stock batch.
- Batch prices are entered as a total amount. The total is divided across its quantity in cents; any remainder cents are assigned to the earliest items so the batch total remains exact. Each stripe keeps its own recorded price, so later restocks do not change past amounts or the price of older stock.
- The stripe table and admin inventory list show the price of the next item that will be consumed.
- Each new or restocked batch records who paid for it. [`bier_totalen.php`](./bier_totalen.php) shows each person's paid amount as credit, their drink costs, and their balance (paid minus consumed), alongside the total value of purchased batches, remaining stock value, and total consumed/owed.
- The admin-only [`beer_admin.php`](./beer_admin.php) page can add drinks, restock active drinks, and deactivate/reactivate drink options. Deactivation does not remove stock or history.
- The **− Streep** button reverses the most recently recorded stripe for that person and drink, removes its charge, and restores its item to the original stock batch.
- There is no deposit tracking or payment settlement. Reversed stripes are deleted rather than kept as correction history.
