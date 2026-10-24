CREATE TABLE beer_people (
	person_id BIGINT UNSIGNED NOT NULL,
	person_name VARCHAR(255) NOT NULL,
	sort_order INT NOT NULL DEFAULT 0,
	active TINYINT(1) NOT NULL DEFAULT 1,
	PRIMARY KEY (person_id),
	KEY active_sort (active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE beer_drinks (
	drink_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	drink_name VARCHAR(128) NOT NULL,
	active TINYINT(1) NOT NULL DEFAULT 1,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (drink_id),
	UNIQUE KEY drink_name (drink_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE beer_stock_batches (
	batch_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	drink_id INT UNSIGNED NOT NULL,
	quantity_total INT UNSIGNED NOT NULL,
	quantity_remaining INT UNSIGNED NOT NULL,
	total_price_cents INT UNSIGNED NOT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (batch_id),
	KEY drink_stock_order (drink_id, batch_id, quantity_remaining),
	CONSTRAINT beer_batches_drink_fk FOREIGN KEY (drink_id)
		REFERENCES beer_drinks (drink_id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE beer_stripes (
	stripe_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	person_id BIGINT UNSIGNED NOT NULL,
	drink_id INT UNSIGNED NOT NULL,
	batch_id BIGINT UNSIGNED NOT NULL,
	price_cents INT UNSIGNED NOT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (stripe_id),
	KEY person_drink (person_id, drink_id),
	KEY drink_history (drink_id, created_at),
	CONSTRAINT beer_stripes_person_fk FOREIGN KEY (person_id)
		REFERENCES beer_people (person_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT beer_stripes_drink_fk FOREIGN KEY (drink_id)
		REFERENCES beer_drinks (drink_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT beer_stripes_batch_fk FOREIGN KEY (batch_id)
		REFERENCES beer_stock_batches (batch_id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
