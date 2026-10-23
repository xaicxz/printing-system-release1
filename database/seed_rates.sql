USE printing_system;
-- DEMO RATES ONLY (PHP), replace with your shop's real rates in Admin > Print rates.
INSERT INTO print_rates (paper_size,print_color,price_per_page) VALUES
('A4','bw',3.00),('A4','color',10.00),
('Letter','bw',3.00),('Letter','color',10.00),
('Legal','bw',4.00),('Legal','color',12.00)
ON DUPLICATE KEY UPDATE id=id;
