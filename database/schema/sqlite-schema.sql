CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "phone" varchar,
  "bio" text
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "categories"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "image" varchar,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "categories_slug_unique" on "categories"("slug");
CREATE TABLE IF NOT EXISTS "brands"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "image" varchar,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "brands_slug_unique" on "brands"("slug");
CREATE TABLE IF NOT EXISTS "orders"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "grand_total" numeric,
  "payment_method" varchar,
  "payment_status" varchar,
  "status" varchar check("status" in('new', 'processing', 'shipped', 'delivered', 'cancelled')) not null default 'new',
  "currency" varchar,
  "shipping_amount" numeric,
  "shipping_method" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "addresses"(
  "id" integer primary key autoincrement not null,
  "order_id" integer not null,
  "first_name" varchar,
  "last_name" varchar,
  "phone" varchar,
  "street_address" text,
  "city" varchar,
  "state" varchar,
  "zip_code" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("order_id") references "orders"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "permissions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "permissions_name_guard_name_unique" on "permissions"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "roles_name_guard_name_unique" on "roles"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "model_has_permissions"(
  "permission_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  primary key("permission_id", "model_id", "model_type")
);
CREATE INDEX "model_has_permissions_model_id_model_type_index" on "model_has_permissions"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "model_has_roles"(
  "role_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("role_id", "model_id", "model_type")
);
CREATE INDEX "model_has_roles_model_id_model_type_index" on "model_has_roles"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "role_has_permissions"(
  "permission_id" integer not null,
  "role_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("permission_id", "role_id")
);
CREATE TABLE IF NOT EXISTS "products"(
  "id" integer primary key autoincrement not null,
  "category_id" integer not null,
  "brand_id" integer not null,
  "name" varchar not null,
  "slug" varchar not null,
  "images" text,
  "description" text,
  "short_description" text,
  "price" numeric,
  "is_active" tinyint(1) not null default('1'),
  "is_featured" tinyint(1) not null default('0'),
  "in_stock" tinyint(1) not null default('1'),
  "on_sale" tinyint(1) not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "meta_title" text,
  "meta_description" text,
  "meta_keywords" text,
  "sku" varchar,
  "price_cents" integer not null,
  "compare_price_cents" integer,
  "cost_price_cents" integer,
  "stock_quantity" integer not null default '0',
  "stock_status" varchar check("stock_status" in('in_stock', 'out_of_stock', 'back_order')) not null default 'in_stock',
  "low_stock_threshold" integer not null default '5',
  "track_inventory" tinyint(1) not null default '1',
  "has_variants" tinyint(1) not null default '0',
  "variant_type" varchar check("variant_type" in('none', 'single', 'multiple')) not null default 'none',
  "variant_attributes" text,
  "attributes" text,
  "variants" text,
  "variant_config" text,
  "migrated_to_json" tinyint(1) not null default '0',
  foreign key("brand_id") references brands("id") on delete cascade on update no action,
  foreign key("category_id") references categories("id") on delete cascade on update no action
);
CREATE UNIQUE INDEX "products_slug_unique" on "products"("slug");
CREATE INDEX "products_price_cents_index" on "products"("price_cents");
CREATE INDEX "products_stock_quantity_index" on "products"("stock_quantity");
CREATE INDEX "products_stock_status_index" on "products"("stock_status");
CREATE TABLE IF NOT EXISTS "product_variants"(
  "id" integer primary key autoincrement not null,
  "product_id" integer not null,
  "sku" varchar not null,
  "name" varchar,
  "price_cents" integer not null,
  "compare_price_cents" integer,
  "cost_price_cents" integer,
  "stock_quantity" integer not null default '0',
  "stock_status" varchar check("stock_status" in('in_stock', 'out_of_stock', 'back_order')) not null default 'in_stock',
  "low_stock_threshold" integer not null default '5',
  "track_inventory" tinyint(1) not null default '1',
  "weight" numeric,
  "dimensions" text,
  "barcode" varchar,
  "images" text,
  "is_active" tinyint(1) not null default '1',
  "is_default" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "options" text,
  "image_url" varchar,
  "override_price" integer,
  "migrated_to_json" tinyint(1) not null default '0',
  foreign key("product_id") references "products"("id") on delete cascade
);
CREATE INDEX "product_variants_product_id_is_default_index" on "product_variants"(
  "product_id",
  "is_default"
);
CREATE UNIQUE INDEX "product_variants_sku_unique" on "product_variants"("sku");
CREATE INDEX "product_variants_price_cents_index" on "product_variants"(
  "price_cents"
);
CREATE INDEX "product_variants_stock_quantity_index" on "product_variants"(
  "stock_quantity"
);
CREATE INDEX "product_variants_stock_status_index" on "product_variants"(
  "stock_status"
);
CREATE INDEX "product_variants_barcode_index" on "product_variants"("barcode");
CREATE INDEX "product_variants_is_default_index" on "product_variants"(
  "is_default"
);
CREATE INDEX "products_has_variants_index" on "products"("has_variants");
CREATE TABLE IF NOT EXISTS "order_items"(
  "id" integer primary key autoincrement not null,
  "order_id" integer not null,
  "product_id" integer not null,
  "quantity" integer not null default('1'),
  "unit_amount" numeric,
  "total_amount" numeric,
  "created_at" datetime,
  "updated_at" datetime,
  "product_variant_id" integer,
  "variant_sku" varchar,
  "variant_attributes" text,
  foreign key("product_id") references products("id") on delete cascade on update no action,
  foreign key("order_id") references orders("id") on delete cascade on update no action,
  foreign key("product_variant_id") references "product_variants"("id") on delete set null
);
CREATE INDEX "order_items_product_id_product_variant_id_index" on "order_items"(
  "product_id",
  "product_variant_id"
);
CREATE INDEX "idx_variants_product_price" on "product_variants"(
  "product_id",
  "is_active",
  "price_cents"
);
CREATE INDEX "idx_variants_stock" on "product_variants"(
  "is_active",
  "stock_status",
  "stock_quantity"
);
CREATE INDEX "idx_variants_default" on "product_variants"(
  "product_id",
  "is_default",
  "is_active"
);
CREATE INDEX "idx_products_listing" on "products"(
  "is_active",
  "has_variants",
  "is_featured"
);
CREATE INDEX "idx_products_price" on "products"("is_active", "price_cents");
CREATE TABLE IF NOT EXISTS "inventory_reservations"(
  "id" integer primary key autoincrement not null,
  "product_id" integer not null,
  "product_variant_id" integer,
  "quantity" integer not null default '1',
  "session_id" varchar,
  "user_id" integer,
  "status" varchar check("status" in('active', 'expired', 'fulfilled', 'cancelled')) not null default 'active',
  "expires_at" datetime not null,
  "reference_type" varchar,
  "reference_id" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("product_id") references "products"("id") on delete cascade,
  foreign key("product_variant_id") references "product_variants"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "inventory_reservations_product_id_status_expires_at_index" on "inventory_reservations"(
  "product_id",
  "status",
  "expires_at"
);
CREATE INDEX "inventory_reservations_product_variant_id_status_expires_at_index" on "inventory_reservations"(
  "product_variant_id",
  "status",
  "expires_at"
);
CREATE INDEX "inventory_reservations_session_id_status_index" on "inventory_reservations"(
  "session_id",
  "status"
);
CREATE INDEX "inventory_reservations_user_id_status_index" on "inventory_reservations"(
  "user_id",
  "status"
);
CREATE INDEX "inventory_reservations_session_id_index" on "inventory_reservations"(
  "session_id"
);
CREATE INDEX "inventory_reservations_status_index" on "inventory_reservations"(
  "status"
);
CREATE INDEX "inventory_reservations_expires_at_index" on "inventory_reservations"(
  "expires_at"
);
CREATE TABLE IF NOT EXISTS "variant_migration_audit"(
  "id" integer primary key autoincrement not null,
  "phase" varchar not null,
  "step" varchar not null,
  "entity_type" varchar not null,
  "entity_id" integer,
  "old_data" text,
  "new_data" text,
  "status" varchar not null default 'pending',
  "error_message" text,
  "validation_errors" text,
  "started_at" datetime,
  "completed_at" datetime,
  "processing_time_ms" integer,
  "rollback_available" tinyint(1) not null default '1',
  "rollback_data" text,
  "batch_id" varchar,
  "user_id" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "variant_migration_audit_phase_step_index" on "variant_migration_audit"(
  "phase",
  "step"
);
CREATE INDEX "variant_migration_audit_entity_type_entity_id_index" on "variant_migration_audit"(
  "entity_type",
  "entity_id"
);
CREATE INDEX "variant_migration_audit_status_index" on "variant_migration_audit"(
  "status"
);
CREATE INDEX "variant_migration_audit_batch_id_index" on "variant_migration_audit"(
  "batch_id"
);
CREATE INDEX "variant_migration_audit_started_at_index" on "variant_migration_audit"(
  "started_at"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2024_06_09_101214_create_categories_table',1);
INSERT INTO migrations VALUES(5,'2024_06_09_101324_create_brands_table',1);
INSERT INTO migrations VALUES(6,'2024_06_09_101341_create_products_table',1);
INSERT INTO migrations VALUES(7,'2024_06_09_101416_create_orders_table',1);
INSERT INTO migrations VALUES(8,'2024_06_09_101429_create_order_items_table',1);
INSERT INTO migrations VALUES(9,'2024_06_09_101447_create_addresses_table',1);
INSERT INTO migrations VALUES(10,'2024_06_12_141143_add_meta_fields_to_products_table',1);
INSERT INTO migrations VALUES(11,'2024_06_12_163819_add_sku_to_products_table',1);
INSERT INTO migrations VALUES(12,'2025_08_07_081241_create_permission_tables',1);
INSERT INTO migrations VALUES(13,'2025_08_07_120000_add_advanced_pricing_and_inventory_to_products_table',1);
INSERT INTO migrations VALUES(14,'2025_08_07_130000_create_product_attributes_table',1);
INSERT INTO migrations VALUES(15,'2025_08_07_130001_create_product_attribute_values_table',1);
INSERT INTO migrations VALUES(16,'2025_08_07_130002_create_product_variants_table',1);
INSERT INTO migrations VALUES(17,'2025_08_07_130003_create_product_variant_attributes_table',1);
INSERT INTO migrations VALUES(18,'2025_08_07_130004_add_variant_support_to_products_table',1);
INSERT INTO migrations VALUES(19,'2025_08_07_130005_add_variant_support_to_order_items_table',1);
INSERT INTO migrations VALUES(20,'2025_08_08_162951_add_phone_bio_to_users_table',1);
INSERT INTO migrations VALUES(21,'2025_08_09_140000_create_specification_attributes_table',2);
INSERT INTO migrations VALUES(22,'2025_08_09_140001_create_specification_attribute_options_table',2);
INSERT INTO migrations VALUES(23,'2025_08_09_140002_create_product_specification_values_table',2);
INSERT INTO migrations VALUES(24,'2025_08_09_140003_create_variant_specification_values_table',2);
INSERT INTO migrations VALUES(25,'2025_08_09_140004_enhance_product_attributes_table',2);
INSERT INTO migrations VALUES(26,'2025_08_09_082534_remove_comparable_fields_from_specification_tables',3);
INSERT INTO migrations VALUES(27,'2025_08_09_134239_add_performance_indexes_for_variants',4);
INSERT INTO migrations VALUES(28,'2025_08_09_134413_create_inventory_reservations_table',5);
INSERT INTO migrations VALUES(29,'2025_08_09_142735_add_json_columns_to_products_table',6);
INSERT INTO migrations VALUES(30,'2025_08_09_165439_add_price_modifier_to_product_attribute_values_table',7);
INSERT INTO migrations VALUES(31,'2025_08_10_050708_add_override_price_to_product_variants_table',8);
INSERT INTO migrations VALUES(32,'2025_08_10_160234_enhance_products_for_json_attributes',9);
INSERT INTO migrations VALUES(33,'2025_08_10_160243_enhance_product_variants_for_json_options',9);
INSERT INTO migrations VALUES(34,'2025_08_10_160248_create_variant_migration_audit',9);
INSERT INTO migrations VALUES(35,'2025_08_10_162014_drop_legacy_variant_tables',9);
