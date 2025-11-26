-- Migration: Add payment_reference to subscriptions table
-- Run this after importing the initial database schema

ALTER TABLE subscriptions ADD COLUMN payment_reference VARCHAR(255) NULL AFTER payment_status;
