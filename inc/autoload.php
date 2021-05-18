<?php

require_once FLANCE_BRANCHES_PATH. '/woocommerce-classes/enqueue.php';
require_once FLANCE_BRANCHES_PATH. '/woocommerce-classes/parent_branch_user.php';
require_once FLANCE_BRANCHES_PATH. '/woocommerce-classes/woocommerce_order_statuses.php';
require_once FLANCE_BRANCHES_PATH. '/woocommerce-classes/class-wc-customer-child.php';
new woocommerce_order_statuses();
require_once FLANCE_BRANCHES_PATH. '/woocommerce-classes/woocommerce_override_template.php';
require_once FLANCE_BRANCHES_PATH. '/woocommerce-classes/wc_checkout_child.php';
require_once FLANCE_BRANCHES_PATH. '/woocommerce-classes/account_statement.php';
require_once FLANCE_BRANCHES_PATH. '/woocommerce-classes/account_statement_pdf.php';