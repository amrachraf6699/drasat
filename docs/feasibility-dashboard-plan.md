# Feasibility Studies Platform Plan

## Summary

Build a Laravel 10 + Inertia + Vue + Tailwind platform where admins manage feasibility-study products, media, FAQs, settings, orders, and bank-transfer approvals; users register/login, buy products, and access purchased documents.

First execution step: save this plan to `docs/feasibility-dashboard-plan.md`, then implement Phase 1 "Admin Core".

Accepted dashboard design reference: `C:\Users\Amr Achraf\.codex\generated_images\019f100b-2abd-70e0-8390-27cd6637f293\ig_0dcc674c3530e3af016a419594615481919603e30460e93d7b.png`.

## Key Decisions

- Use separate `admins` table/model/guard, not an admin column on `users`.
- Use Spatie roles/permissions on the `admin` guard.
- Use Spatie Translatable JSON columns for bilingual product, FAQ, and setting content.
- Use `/public/font.otf` globally through Tailwind/app CSS.
- Use `lucide-vue-next` for icons; no hand-written SVG icons.
- Use PayPal JavaScript SDK on checkout and Laravel server endpoints for PayPal Orders create/capture.
- Default locale: `en`; supported locales: `en`, `ar`; Arabic UI uses RTL.

## Phases

1. **Project Stack + Admin Foundation**
   - Install/configure Inertia Laravel, Vue 3, Tailwind, Vite Vue plugin, `lucide-vue-next`, Socialite, Spatie Permission, and Spatie Translatable.
   - Add Inertia app shell, global font, Tailwind config, RTL-aware layout helpers.
   - Add `Admin` model, `admins` guard/provider/password broker, `/admin/login`, `/admin/logout`, and protected `/admin` route group.
   - Seed `super-admin` role and admin user: `admin@drasa.test` / `password`.

2. **Database + Domain Models**
   - Add `products`, `media`, `faqs`, and `settings`; translated content lives in JSON columns on the owning table.
   - Product relations:
     - `cover`: `morphOne(Media::class)` where `collection_name = cover` and `file_type = image`.
     - `documents`: `morphMany(Media::class)` where `collection_name = documents` and `file_type = document`.
   - Add order-ready tables for later phases: `carts`, `cart_items`, `orders`, `order_items`, `payments`, `bank_transfers`, `purchases`; OAuth provider columns live on `users`.

3. **Admin Dashboard Core**
   - Implement accepted responsive dashboard UI: sidebar desktop, bottom nav/drawer mobile, top search, EN/AR toggle, admin profile.
   - Dashboard cards: total products, orders, pending transfers, revenue, users.
   - Dashboard panels: recent orders, pending bank transfers, product status, quick actions.

4. **Admin CRUD**
   - Products: translated title/description, price cents, status, cover upload, document uploads.
   - FAQs: translated question/answer, status, sort order.
   - Settings: group, input type, key, value; translated values where needed; media settings use `media` record ID in `value`.
   - Admin users/roles screen using permissions:
     `products.*`, `orders.view`, `transfers.review`, `users.view`, `faqs.manage`, `settings.manage`, `roles.manage`.

5. **Public/User Features**
   - User register/login with name, email, password.
   - Google/Facebook OAuth via Socialite and OAuth provider columns on `users`.
   - Product listing/detail pages, cart, checkout entry.

6. **Payments + Delivery**
   - PayPal flow: create order server-side, approve in PayPal JS SDK, capture server-side, mark order paid, grant `purchases`.
   - Bank transfer flow: user uploads proof or reference number; admin approves/rejects; approval grants `purchases`.
   - User library shows purchased products and downloadable documents only for owned products.

## Test Plan

- Run migrations and seeders on SQLite.
- Feature tests for admin guard isolation, admin login, permission-protected routes, seed users/admins.
- Model tests for product `cover` and `documents` media relationships.
- CRUD tests for products, FAQs, settings, uploads, and bilingual translation fallback.
- Payment tests with mocked PayPal client: create, capture, failure, duplicate capture.
- Bank transfer tests: pending, approve, reject, purchase grant.
- Access tests: users cannot download documents for unpurchased products.
- Frontend checks: `npm run build`, responsive desktop/mobile dashboard, RTL Arabic layout.

## Assumptions

- First implementation scope is "Admin Core", not full commerce.
- Uploaded product documents are stored on a private disk and downloaded through authorized Laravel routes.
- OAuth tokens are not stored unless required later; only provider, provider ID, and avatar are stored on `users`.
- Official implementation references: Inertia setup, Laravel Socialite, Spatie Permission, and PayPal JS SDK/Orders API docs.
