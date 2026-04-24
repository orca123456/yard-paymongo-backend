CREATE TABLE IF NOT EXISTS admins (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS preorders (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    contact VARCHAR(50),
    fb_link TEXT,
    address TEXT,
    product VARCHAR(150),
    price NUMERIC(10, 2) DEFAULT 0,
    notes TEXT,
    payment_method VARCHAR(100),
    order_status VARCHAR(30) DEFAULT 'pending',
    payment_status VARCHAR(30) DEFAULT 'pending',
    paymongo_checkout_id VARCHAR(150),
    paymongo_payment_id VARCHAR(150),
    paid_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE preorders ADD COLUMN IF NOT EXISTS email VARCHAR(150);
ALTER TABLE preorders ADD COLUMN IF NOT EXISTS paid_at TIMESTAMPTZ;
ALTER TABLE preorders ADD COLUMN IF NOT EXISTS paymongo_checkout_id VARCHAR(150);
ALTER TABLE preorders ADD COLUMN IF NOT EXISTS paymongo_payment_id VARCHAR(150);
ALTER TABLE preorders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(30) DEFAULT 'pending';
ALTER TABLE preorders ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ DEFAULT NOW();
