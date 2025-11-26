-- Migration: Add price_per_month to subjects table
-- Run this after importing the initial database schema

ALTER TABLE subjects ADD COLUMN price_per_month DECIMAL(10, 2) DEFAULT 0.00 AFTER description;
