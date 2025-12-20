<?php
// financial_reports.php - Financial Reports Generator
require_once 'config.php';
check_office_login();

// Sanitize session values
$office_id = isset($_SESSION['office_id']) ? (int) $_SESSION['office_id'] : 0;
$office_sql = "SELECT * FROM office_list WHERE id = '$office_id'";
$office_result = mysqli_query($conn, $office_sql);
$office = mysqli_fetch_assoc($office_result);

// Calculate subscription days (handle missing/invalid purchase_date)
$days_left = 0;
if (!empty($office) && !empty($office['purchase_date']) && $office['purchase_date'] !== '0000-00-00') {
    try {
        $purchase_date = new DateTime($office['purchase_date']);
        $current_date = new DateTime();
        $interval = $current_date->diff($purchase_date);
        $days_left = 30 - $interval->days;
        if ($days_left < 0) $days_left = 0;
    } catch (Exception $e) {
        // If invalid date, do not block access; set default days_left
        $days_left = 30;
    }
} else {
    // If no purchase date available, assume active (30 days) rather than throwing errors
    $days_left = 30;
}

// Check subscription
if ($days_left <= 0) {
    echo "<script>alert('Your subscription has expired. Please renew to access reports.'); window.location.href='finance.php';</script>";
    exit();
}

// Get office email (safe)
$office_email = isset($_SESSION['office_email']) ? mysqli_real_escape_string($conn, $_SESSION['office_email']) : '';

// Get user information (safe)
$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$user_sql = "SELECT * FROM user_list WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

// Report types
$report_types = [
    'overall' => 'Overall Report',
    'yearly' => 'Yearly Report',
    'monthly' => 'Monthly Report',
    'quarterly' => 'Quarterly Report',
    'half_yearly' => 'Half-Yearly Report'
];

// Report categories
$report_categories = [
    'journal' => 'Journal',
    'ledger' => 'Ledger',
    'trial_balance' => 'Trial Balance',
    'adjusted_trial_balance' => 'Adjusted Trial Balance',
    'income_statement' => 'Income Statement',
    'retained_earnings' => 'Retained Earnings Statement',
    'balance_sheet' => 'Balance Sheet',
    'closing_entry' => 'Closing Entry',
    'post_closing_entry' => 'Post-Closing Entry'
];

// Years for dropdown (from first entry to current year)
$years_sql = "SELECT YEAR(entry_date) as year FROM user_finance_entry 
              WHERE office_email = '$office_email' 
              GROUP BY YEAR(entry_date) ORDER BY year DESC";
$years_result = mysqli_query($conn, $years_sql);
$years = [];
while ($row = mysqli_fetch_assoc($years_result)) {
    $years[] = $row['year'];
}
if (empty($years)) {
    $years = [date('Y')];
}

// Months
$months = [
    '01' => 'January', '02' => 'February', '03' => 'March',
    '04' => 'April', '05' => 'May', '06' => 'June',
    '07' => 'July', '08' => 'August', '09' => 'September',
    '10' => 'October', '11' => 'November', '12' => 'December'
];

// Quarters
$quarters = [
    '1' => 'Q1 (Jan-Mar)',
    '2' => 'Q2 (Apr-Jun)',
    '3' => 'Q3 (Jul-Sep)',
    '4' => 'Q4 (Oct-Dec)'
];

// Half-years
$half_years = [
    '1' => 'H1 (Jan-Jun)',
    '2' => 'H2 (Jul-Dec)'
];

// Currency conversion helper (simple offline rates)
function get_exchange_rates() {
    // Rates are expressed as: 1 unit of key = X USD
    return [
        'USD' => 1.00,
        'EUR' => 1.10,
        'GBP' => 1.27,
        'JPY' => 0.0069,
        'AUD' => 0.66,
        'CAD' => 0.74,
        'CHF' => 1.10,
        'CNY' => 0.14,
        'INR' => 0.012,
        'SGD' => 0.74,
        'AED' => 0.27,
        'SAR' => 0.27,
        'PKR' => 0.0036,
        'MYR' => 0.21,
        'THB' => 0.028,
        'KRW' => 0.00075,
        'BDT' => 0.0094
    ];
}

function convert_amount($amount, $from_currency, $to_currency) {
    $from = strtoupper(trim($from_currency ?: 'USD'));
    $to = strtoupper(trim($to_currency ?: 'USD'));

    if ($from === $to) return $amount;

    $rates = get_exchange_rates();

    // Convert via USD as intermediate
    $from_to_usd = isset($rates[$from]) ? $rates[$from] : null;
    $to_to_usd = isset($rates[$to]) ? $rates[$to] : null;

    // If we don't know rates, fall back to 1:1 (no conversion)
    if ($from_to_usd === null || $to_to_usd === null) return $amount;

    // amount in USD
    $amount_usd = $amount * $from_to_usd;

    if ($to === 'USD') return $amount_usd;

    // Convert USD to target currency
    return $amount_usd / $to_to_usd;
}

// Initialize variables
$report_data = [];
$report_summary = [];
$selected_params = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Guard POST values before using sanitize_input
    $report_type = isset($_POST['report_type']) ? sanitize_input($_POST['report_type'], $conn) : 'overall';
    $report_category = isset($_POST['report_category']) ? sanitize_input($_POST['report_category'], $conn) : '';
    
    $selected_params = [
        'type' => $report_type,
        'category' => $report_category,
        'time' => []
    ];
    
    // Build base query
    $sql = "SELECT * FROM user_finance_entry WHERE office_email = '$office_email'";
    
    // Add date filters based on report type
    if ($report_type !== 'overall') {
        $year = isset($_POST['year']) ? sanitize_input($_POST['year'], $conn) : '';
        $year = intval($year);
        $selected_params['time']['year'] = $year;
        
        switch ($report_type) {
            case 'yearly':
                if ($year > 0) {
                    $sql .= " AND YEAR(entry_date) = $year";
                    $selected_params['time']['display'] = "Year: $year";
                }
                break;
                
            case 'monthly':
                $month = isset($_POST['month']) ? sanitize_input($_POST['month'], $conn) : '';
                $month = intval($month);
                if ($year > 0 && $month > 0) {
                    $sql .= " AND YEAR(entry_date) = $year AND MONTH(entry_date) = $month";
                    $selected_params['time']['display'] = "Month: " . ($months[str_pad($month,2,'0',STR_PAD_LEFT)] ?? $month) . " $year";
                }
                break;
                
            case 'quarterly':
                $quarter = isset($_POST['quarter']) ? intval(sanitize_input($_POST['quarter'], $conn)) : 0;
                $selected_params['time']['quarter'] = $quarter;
                $selected_params['time']['display'] = "Quarter: $quarter, Year: $year";

                if ($year > 0 && $quarter > 0) {
                    if ($quarter == 1) {
                        $sql .= " AND YEAR(entry_date) = $year AND MONTH(entry_date) BETWEEN 1 AND 3";
                    } elseif ($quarter == 2) {
                        $sql .= " AND YEAR(entry_date) = $year AND MONTH(entry_date) BETWEEN 4 AND 6";
                    } elseif ($quarter == 3) {
                        $sql .= " AND YEAR(entry_date) = $year AND MONTH(entry_date) BETWEEN 7 AND 9";
                    } elseif ($quarter == 4) {
                        $sql .= " AND YEAR(entry_date) = $year AND MONTH(entry_date) BETWEEN 10 AND 12";
                    }
                }
                break;
                
            case 'half_yearly':
                $half = isset($_POST['half']) ? intval(sanitize_input($_POST['half'], $conn)) : 0;
                $selected_params['time']['half'] = $half;
                $selected_params['time']['display'] = "Half: $half, Year: $year";

                if ($year > 0 && $half > 0) {
                    if ($half == 1) {
                        $sql .= " AND YEAR(entry_date) = $year AND MONTH(entry_date) BETWEEN 1 AND 6";
                    } elseif ($half == 2) {
                        $sql .= " AND YEAR(entry_date) = $year AND MONTH(entry_date) BETWEEN 7 AND 12";
                    }
                }
                break;
        }
    } else {
        $selected_params['time']['display'] = "All Time";
    }
    
    // Add ordering
    $sql .= " ORDER BY entry_date ASC, id ASC";
    
    // Execute query
    $result = mysqli_query($conn, $sql);
    $total_entries = $result ? mysqli_num_rows($result) : 0;
    
    // Process entries: collect raw entries and detect currencies first
    $raw_entries = [];
    $currencies = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $raw_entries[] = $row;
            $currencies[strtoupper(trim($row['currency'] ?: ''))] = true;
        }
    }

    // Decide target currency: if more than one currency present, convert to USD
    $currency_list = array_values(array_filter(array_unique(array_keys($currencies))));
    $target_currency = 'USD';
    if (count($currency_list) === 1 && !empty($currency_list[0])) {
        // all entries use the same currency -> keep it
        $target_currency = $currency_list[0];
    }

    // Now build report_data and compute account balances using converted amounts when needed
    $total_debit = 0;
    $total_credit = 0;
    $accounts = [];

    foreach ($raw_entries as $row) {
        $amount = floatval($row['transaction_amount']);
        $from_currency = strtoupper(trim($row['currency'] ?: $target_currency));

        // Convert amount to target currency if necessary
        if ($from_currency !== $target_currency) {
            $converted = convert_amount($amount, $from_currency, $target_currency);
        } else {
            $converted = $amount;
        }

        // Append converted fields to report row for downstream rendering
        $row['converted_amount'] = $converted;
        $row['display_currency'] = $target_currency;
        $report_data[] = $row;

        // Track accounts using converted amounts
        $debit_account = $row['debit_account'];
        $credit_account = $row['credit_account'];

        if (!isset($accounts[$debit_account])) {
            $accounts[$debit_account] = ['debit' => 0, 'credit' => 0];
        }
        if (!isset($accounts[$credit_account])) {
            $accounts[$credit_account] = ['debit' => 0, 'credit' => 0];
        }

        $accounts[$debit_account]['debit'] += $converted;
        $accounts[$credit_account]['credit'] += $converted;

        $total_debit += $converted;
        $total_credit += $converted;
    }
    
    // Prepare report summary
    $report_summary = [
        'total_entries' => $total_entries,
        'total_debit' => $total_debit,
        'total_credit' => $total_credit,
        'total_accounts' => count($accounts),
        'accounts' => $accounts,
        'currency' => $target_currency
    ];
}

// Function to generate report content based on category
function generateReportContent($report_category, $report_data, $report_summary, $selected_params, $office_name) {
    $content = '';
    $current_date = date('d/m/Y H:i:s');
    
    // Report Header
    $content .= '<div class="report-header">';
    $content .= '<h1>' . htmlspecialchars($office_name) . '</h1>';
    $content .= '<h2>' . ucwords(str_replace('_', ' ', $report_category)) . ' Report</h2>';
    $content .= '<p>Report Type: ' . ucwords(str_replace('_', ' ', $selected_params['type'])) . '</p>';
    $content .= '<p>Period: ' . $selected_params['time']['display'] . '</p>';
    $content .= '<p>Currency: ' . htmlspecialchars($report_summary['currency'] ?? '') . '</p>';
    $content .= '<p>Generated: ' . $current_date . '</p>';
    $content .= '</div>';
    
    // Summary Section
    $content .= '<div class="report-summary">';
    $content .= '<h3>Summary</h3>';
    $content .= '<table>';
    $content .= '<tr><td>Total Entries:</td><td>' . number_format($report_summary['total_entries']) . '</td></tr>';
    $content .= '<tr><td>Total Debit:</td><td>' . number_format($report_summary['total_debit'], 2) . '</td></tr>';
    $content .= '<tr><td>Total Credit:</td><td>' . number_format($report_summary['total_credit'], 2) . '</td></tr>';
    $content .= '<tr><td>Total Accounts:</td><td>' . $report_summary['total_accounts'] . '</td></tr>';
    $content .= '</table>';
    $content .= '</div>';
    
    // Detailed Report based on category
    switch ($report_category) {
        case 'journal':
            $content .= generateJournalReport($report_data);
            break;
        case 'ledger':
            $content .= generateLedgerReport($report_data, $report_summary['accounts']);
            break;
        case 'trial_balance':
        case 'adjusted_trial_balance':
            $content .= generateTrialBalanceReport($report_summary['accounts']);
            break;
        case 'income_statement':
            $content .= generateIncomeStatement($report_data, $report_summary['accounts']);
            break;
        case 'balance_sheet':
            $content .= generateBalanceSheet($report_summary['accounts']);
            break;
        default:
            $content .= generateGeneralReport($report_data);
            break;
    }
    
    return $content;
}

// Helper functions for different report types
function generateJournalReport($data) {
    $content = '<div class="journal-report">';
    $content .= '<h3>Journal Entries</h3>';
    $content .= '<table>';
    $content .= '<thead>';
    $content .= '<tr>';
    $content .= '<th>Date</th>';
    $content .= '<th>Account Title</th>';
    $content .= '<th>Debit</th>';
    $content .= '<th>Credit</th>';
    $content .= '<th>Description</th>';
    $content .= '</tr>';
    $content .= '</thead>';
    $content .= '<tbody>';
    
    foreach ($data as $entry) {
        $date = date('d/m/Y', strtotime($entry['entry_date']));
        $display_currency = isset($entry['display_currency']) ? htmlspecialchars($entry['display_currency']) : (isset($entry['currency']) ? htmlspecialchars($entry['currency']) : '');
        $amount = isset($entry['converted_amount']) ? floatval($entry['converted_amount']) : floatval($entry['transaction_amount']);

        $content .= '<tr>';
        $content .= '<td>' . $date . '</td>';
        $content .= '<td>' . htmlspecialchars($entry['debit_account']) . '</td>';
        $content .= '<td>' . number_format($amount, 2) . ' ' . $display_currency . '</td>';
        $content .= '<td></td>';
        $content .= '<td>' . htmlspecialchars($entry['comments'] ?: 'N/A') . '</td>';
        $content .= '</tr>';

        $content .= '<tr>';
        $content .= '<td>' . $date . '</td>';
        $content .= '<td>' . htmlspecialchars($entry['credit_account']) . '</td>';
        $content .= '<td></td>';
        $content .= '<td>' . number_format($amount, 2) . ' ' . $display_currency . '</td>';
        $content .= '<td>' . htmlspecialchars($entry['comments'] ?: 'N/A') . '</td>';
        $content .= '</tr>';
    }
    
    $content .= '</tbody>';
    $content .= '</table>';
    $content .= '</div>';
    
    return $content;
}

function generateLedgerReport($data, $accounts) {
    $content = '<div class="ledger-report">';
    $content .= '<h3>General Ledger</h3>';
    
    foreach ($accounts as $account_name => $balances) {
        $content .= '<div class="account-section">';
        $content .= '<h4>' . htmlspecialchars($account_name) . '</h4>';
        $content .= '<table>';
        $content .= '<thead>';
        $content .= '<tr>';
        $content .= '<th>Date</th>';
        $content .= '<th>Description</th>';
        $content .= '<th>Debit</th>';
        $content .= '<th>Credit</th>';
        $content .= '<th>Balance</th>';
        $content .= '</tr>';
        $content .= '</thead>';
        $content .= '<tbody>';
        
        $balance = 0;
        
        // Filter entries for this account
        foreach ($data as $entry) {
            if ($entry['debit_account'] == $account_name || $entry['credit_account'] == $account_name) {
                $date = date('d/m/Y', strtotime($entry['entry_date']));
                $amount = isset($entry['converted_amount']) ? floatval($entry['converted_amount']) : floatval($entry['transaction_amount']);
                $display_currency = isset($entry['display_currency']) ? htmlspecialchars($entry['display_currency']) : (isset($entry['currency']) ? htmlspecialchars($entry['currency']) : '');

                if ($entry['debit_account'] == $account_name) {
                    $balance += $amount;
                    $debit = number_format($amount, 2) . ' ' . $display_currency;
                    $credit = '';
                } else {
                    $balance -= $amount;
                    $debit = '';
                    $credit = number_format($amount, 2) . ' ' . $display_currency;
                }

                $content .= '<tr>';
                $content .= '<td>' . $date . '</td>';
                $content .= '<td>' . htmlspecialchars($entry['comments'] ?: 'Journal Entry') . '</td>';
                $content .= '<td>' . $debit . '</td>';
                $content .= '<td>' . $credit . '</td>';
                $content .= '<td>' . number_format($balance, 2) . ' ' . $display_currency . '</td>';
                $content .= '</tr>';
            }
        }
        
        $content .= '</tbody>';
        $content .= '</table>';
        $content .= '</div>';
    }
    
    $content .= '</div>';
    
    return $content;
}

function generateTrialBalanceReport($accounts) {
    $content = '<div class="trial-balance-report">';
    $content .= '<h3>Trial Balance</h3>';
    $content .= '<table>';
    $content .= '<thead>';
    $content .= '<tr>';
    $content .= '<th>Account</th>';
    $content .= '<th>Debit</th>';
    $content .= '<th>Credit</th>';
    $content .= '</tr>';
    $content .= '</thead>';
    $content .= '<tbody>';
    
    $total_debit = 0;
    $total_credit = 0;
    
    foreach ($accounts as $account_name => $balances) {
        $debit = $balances['debit'];
        $credit = $balances['credit'];
        
        // Only show accounts with non-zero balances
        if ($debit > 0 || $credit > 0) {
            $content .= '<tr>';
            $content .= '<td>' . htmlspecialchars($account_name) . '</td>';
            $content .= '<td>' . ($debit > 0 ? number_format($debit, 2) : '') . '</td>';
            $content .= '<td>' . ($credit > 0 ? number_format($credit, 2) : '') . '</td>';
            $content .= '</tr>';
            
            $total_debit += $debit;
            $total_credit += $credit;
        }
    }
    
    // Total row
    $content .= '<tr class="total-row">';
    $content .= '<td><strong>Total</strong></td>';
    $content .= '<td><strong>' . number_format($total_debit, 2) . '</strong></td>';
    $content .= '<td><strong>' . number_format($total_credit, 2) . '</strong></td>';
    $content .= '</tr>';
    
    $content .= '</tbody>';
    $content .= '</table>';
    $content .= '</div>';
    
    return $content;
}

function generateIncomeStatement($data, $accounts) {
    $content = '<div class="income-statement">';
    $content .= '<h3>Income Statement</h3>';
    
    // Revenue
    $revenue = 0;
    $expenses = 0;
    
    // Simple calculation (in real app, you'd need proper account classification)
    foreach ($accounts as $account_name => $balances) {
        $lower_name = strtolower($account_name);
        
        if (strpos($lower_name, 'revenue') !== false || 
            strpos($lower_name, 'sales') !== false ||
            strpos($lower_name, 'income') !== false) {
            $revenue += $balances['credit'] - $balances['debit'];
        } elseif (strpos($lower_name, 'expense') !== false ||
                 strpos($lower_name, 'cost') !== false ||
                 strpos($lower_name, 'salary') !== false) {
            $expenses += $balances['debit'] - $balances['credit'];
        }
    }
    
    $net_income = $revenue - $expenses;
    
    $content .= '<table>';
    $content .= '<tr><td><strong>Revenue</strong></td><td></td></tr>';
    $content .= '<tr><td>Total Revenue</td><td>' . number_format($revenue, 2) . '</td></tr>';
    $content .= '<tr><td><strong>Expenses</strong></td><td></td></tr>';
    $content .= '<tr><td>Total Expenses</td><td>' . number_format($expenses, 2) . '</td></tr>';
    $content .= '<tr class="total-row"><td><strong>Net Income</strong></td><td><strong>' . number_format($net_income, 2) . '</strong></td></tr>';
    $content .= '</table>';
    $content .= '</div>';
    
    return $content;
}

function generateBalanceSheet($accounts) {
    $content = '<div class="balance-sheet">';
    $content .= '<h3>Balance Sheet</h3>';
    
    $assets = 0;
    $liabilities = 0;
    $equity = 0;
    
    // Simple classification (in real app, you'd need proper account mapping)
    foreach ($accounts as $account_name => $balances) {
        $lower_name = strtolower($account_name);
        
        // This is a simplified classification
        if (strpos($lower_name, 'cash') !== false || 
            strpos($lower_name, 'bank') !== false ||
            strpos($lower_name, 'account receivable') !== false ||
            strpos($lower_name, 'inventory') !== false) {
            $assets += $balances['debit'] - $balances['credit'];
        } elseif (strpos($lower_name, 'account payable') !== false ||
                 strpos($lower_name, 'loan') !== false ||
                 strpos($lower_name, 'debt') !== false) {
            $liabilities += $balances['credit'] - $balances['debit'];
        } elseif (strpos($lower_name, 'capital') !== false ||
                 strpos($lower_name, 'equity') !== false ||
                 strpos($lower_name, 'retained') !== false) {
            $equity += $balances['credit'] - $balances['debit'];
        }
    }
    
    $total_liabilities_equity = $liabilities + $equity;
    
    $content .= '<div class="balance-sheet-columns">';
    
    // Assets
    $content .= '<div class="assets-column">';
    $content .= '<h4>Assets</h4>';
    $content .= '<table>';
    $content .= '<tr><td>Total Assets</td><td>' . number_format($assets, 2) . '</td></tr>';
    $content .= '</table>';
    $content .= '</div>';
    
    // Liabilities & Equity
    $content .= '<div class="liabilities-equity-column">';
    $content .= '<h4>Liabilities & Equity</h4>';
    $content .= '<table>';
    $content .= '<tr><td><strong>Liabilities</strong></td><td></td></tr>';
    $content .= '<tr><td>Total Liabilities</td><td>' . number_format($liabilities, 2) . '</td></tr>';
    $content .= '<tr><td><strong>Equity</strong></td><td></td></tr>';
    $content .= '<tr><td>Total Equity</td><td>' . number_format($equity, 2) . '</td></tr>';
    $content .= '<tr class="total-row"><td><strong>Total Liabilities & Equity</strong></td><td><strong>' . number_format($total_liabilities_equity, 2) . '</strong></td></tr>';
    $content .= '</table>';
    $content .= '</div>';
    
    $content .= '</div>';
    $content .= '</div>';
    
    return $content;
}

function generateGeneralReport($data) {
    $content = '<div class="general-report">';
    $content .= '<h3>Financial Report</h3>';
    $content .= '<table>';
    $content .= '<thead>';
    $content .= '<tr>';
    $content .= '<th>Date</th>';
    $content .= '<th>Type</th>';
    $content .= '<th>Debit Account</th>';
    $content .= '<th>Credit Account</th>';
    $content .= '<th>Amount</th>';
    $content .= '<th>Reference</th>';
    $content .= '</tr>';
    $content .= '</thead>';
    $content .= '<tbody>';
    
    foreach ($data as $entry) {
        $date = date('d/m/Y', strtotime($entry['entry_date']));
        $content .= '<tr>';
        $content .= '<td>' . $date . '</td>';
        $content .= '<td>' . ucfirst($entry['entry_type']) . '</td>';
        $content .= '<td>' . htmlspecialchars($entry['debit_account']) . '</td>';
        $content .= '<td>' . htmlspecialchars($entry['credit_account']) . '</td>';
        $display_currency = isset($entry['display_currency']) ? $entry['display_currency'] : (isset($entry['currency']) ? $entry['currency'] : '');
        $amount_to_show = isset($entry['converted_amount']) ? $entry['converted_amount'] : $entry['transaction_amount'];
        $content .= '<td>' . number_format($amount_to_show, 2) . ' ' . htmlspecialchars($display_currency) . '</td>';
        $content .= '<td>' . htmlspecialchars($entry['reference'] ?: 'N/A') . '</td>';
        $content .= '</tr>';
    }
    
    $content .= '</tbody>';
    $content .= '</table>';
    $content .= '</div>';
    
    return $content;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - <?php echo htmlspecialchars($office['business_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #2ecc71;
            --warning: #e74c3c;
            --light: #ffffff;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --background: #f8f9fa;
            --card-bg: #ffffff;
            --border: #e9ecef;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--background);
            color: #333;
            line-height: 1.6;
            padding-top: 80px;
        }

        /* Navigation */
        .finance-nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: var(--light);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            height: 80px;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .back-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 10px;
            border-radius: 5px;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background-color: var(--background);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
        }

        .subscription-badge {
            background-color: var(--accent);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .main-content {
            padding: 40px 0;
        }

        /* Form Container */
        .form-container {
            background-color: var(--card-bg);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border);
        }

        .form-header h1 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--gray);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .required::after {
            content: " *";
            color: var(--warning);
        }

        select, input[type="text"], input[type="number"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: var(--light);
        }

        select:focus, input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .time-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background-color: var(--background);
            padding: 20px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .time-fields.hidden {
            display: none;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 40px;
        }

        .submit-btn, .cancel-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .submit-btn {
            background-color: var(--accent);
            color: white;
        }

        .submit-btn:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .cancel-btn {
            background-color: var(--light);
            color: var(--dark);
            border: 2px solid var(--border);
        }

        .cancel-btn:hover {
            background-color: var(--background);
            border-color: var(--gray);
        }

        /* Report Preview */
        .report-preview {
            margin-top: 40px;
            padding: 30px;
            background-color: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            display: <?php echo !empty($report_data) ? 'block' : 'none'; ?>;
        }

        .report-preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border);
        }

        .report-preview-header h3 {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .report-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .view-btn {
            background-color: var(--secondary);
            color: white;
        }

        .download-btn {
            background-color: var(--accent);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Report Content */
        .report-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            border: 1px solid var(--border);
            max-height: 500px;
            overflow-y: auto;
        }

        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border);
        }

        .report-header h1 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .report-header h2 {
            color: var(--secondary);
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .report-summary {
            background-color: var(--background);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .report-summary table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-summary td {
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }

        .report-summary td:first-child {
            font-weight: 600;
            width: 60%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th {
            background-color: var(--primary);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
        }

        tr:hover {
            background-color: var(--background);
        }

        .total-row {
            background-color: var(--background);
            font-weight: 600;
        }

        .account-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .account-section h4 {
            color: var(--primary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .balance-sheet-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .assets-column, .liabilities-equity-column {
            padding: 20px;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background-color: white;
            border-radius: 15px;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .modal-header {
            position: sticky;
            top: 0;
            background: linear-gradient(135deg, var(--primary) 0%, #3498db 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .modal-header h3 {
            font-size: 1.5rem;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
        }

        .modal-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            background-color: white;
            color: var(--primary);
        }

        .modal-btn:hover {
            background-color: var(--background);
        }

        .modal-body {
            padding: 30px;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.8rem;
            cursor: pointer;
            padding: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .finance-nav {
                padding: 0 15px;
                height: 70px;
            }

            .page-title {
                font-size: 1.2rem;
            }

            .form-container {
                padding: 20px;
            }

            .form-row, .time-fields, .balance-sheet-columns {
                grid-template-columns: 1fr;
            }

            .form-actions, .report-actions, .modal-actions {
                flex-direction: column;
            }

            .modal-content {
                width: 95%;
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="finance-nav">
        <div class="nav-left">
            <a href="finance.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Finance
            </a>
            <div class="page-title">Financial Reports</div>
        </div>
        <div class="subscription-badge">
            <i class="fas fa-clock"></i> <?php echo $days_left; ?> Days Left
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <!-- Form Container -->
            <div class="form-container">
                <div class="form-header">
                    <h1><i class="fas fa-chart-bar"></i> Generate Financial Report</h1>
                    <p>Generate detailed financial reports for <?php echo htmlspecialchars($office['business_name']); ?></p>
                </div>

                <form method="POST" action="" id="reportForm">
                    <!-- Report Type -->
                    <div class="form-group">
                        <label class="required">Report Type</label>
                        <select name="report_type" id="reportType" required onchange="toggleTimeFields()">
                            <option value="">Select Report Type</option>
                            <?php foreach($report_types as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo isset($_POST['report_type']) && $_POST['report_type'] == $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Time Fields (Dynamic) -->
                    <div class="time-fields hidden" id="timeFields">
                        <!-- Year Field (always shown for non-overall reports) -->
                        <div class="form-group" id="yearField">
                            <label class="required">Year</label>
                            <select name="year" id="yearSelect" required>
                                <option value="">Select Year</option>
                                <?php foreach($years as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo isset($_POST['year']) && $_POST['year'] == $year ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Month Field -->
                        <div class="form-group hidden" id="monthField">
                            <label class="required">Month</label>
                            <select name="month" id="monthSelect">
                                <option value="">Select Month</option>
                                <?php foreach($months as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo isset($_POST['month']) && $_POST['month'] == $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Quarter Field -->
                        <div class="form-group hidden" id="quarterField">
                            <label class="required">Quarter</label>
                            <select name="quarter" id="quarterSelect">
                                <option value="">Select Quarter</option>
                                <?php foreach($quarters as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo isset($_POST['quarter']) && $_POST['quarter'] == $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Half Year Field -->
                        <div class="form-group hidden" id="halfField">
                            <label class="required">Half Year</label>
                            <select name="half" id="halfSelect">
                                <option value="">Select Half Year</option>
                                <?php foreach($half_years as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo isset($_POST['half']) && $_POST['half'] == $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Report Category -->
                    <div class="form-group">
                        <label class="required">Report Category</label>
                        <select name="report_category" required>
                            <option value="">Select Report Category</option>
                            <?php foreach($report_categories as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo isset($_POST['report_category']) && $_POST['report_category'] == $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-chart-line"></i> Generate Report
                        </button>
                        <a href="finance.php" class="cancel-btn">
                            <i class="fas fa-times-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Report Preview -->
            <?php if (!empty($report_data)): ?>
                <div class="report-preview">
                    <div class="report-preview-header">
                        <h3>Report Generated Successfully!</h3>
                        <div class="report-actions">
                            <button class="action-btn view-btn" onclick="showReportModal()">
                                <i class="fas fa-eye"></i> View Full Report
                            </button>
                            <button class="action-btn download-btn" onclick="downloadPDF()">
                                <i class="fas fa-download"></i> Download PDF
                            </button>
                        </div>
                    </div>
                    
                    <div class="report-content" id="reportContent">
                        <?php 
                        echo generateReportContent(
                            $report_category, 
                            $report_data, 
                            $report_summary, 
                            $selected_params, 
                            $office['business_name']
                        );
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Information Section -->
            <div style="background-color: #e8f4fd; border-radius: 10px; padding: 20px; margin-top: 20px;">
                <h3 style="color: var(--primary); margin-bottom: 10px;">
                    <i class="fas fa-lightbulb"></i> Report Types Explained:
                </h3>
                <ul style="color: var(--dark); padding-left: 20px;">
                    <li><strong>Journal:</strong> Lists all journal entries chronologically</li>
                    <li><strong>Ledger:</strong> Shows account-wise transaction details</li>
                    <li><strong>Trial Balance:</strong> Lists all account balances</li>
                    <li><strong>Income Statement:</strong> Shows revenue and expenses</li>
                    <li><strong>Balance Sheet:</strong> Displays assets, liabilities, and equity</li>
                    <li><strong>Retained Earnings:</strong> Shows changes in retained earnings</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Report Modal -->
    <div class="modal" id="reportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-alt"></i> Financial Report</h3>
                <div class="modal-actions">
                    <button class="modal-btn" onclick="downloadPDF()">
                        <i class="fas fa-download"></i> PDF
                    </button>
                    <button class="close-modal" onclick="closeReportModal()">&times;</button>
                </div>
            </div>
            <div class="modal-body" id="modalReportContent">
                <!-- Report content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Initialize time fields based on report type
        document.addEventListener('DOMContentLoaded', function() {
            toggleTimeFields();
            
            <?php if (!empty($report_data)): ?>
                // Copy report content to modal
                document.getElementById('modalReportContent').innerHTML = 
                    document.getElementById('reportContent').innerHTML;
            <?php endif; ?>
        });

        // Toggle time fields based on report type
        function toggleTimeFields() {
            const reportType = document.getElementById('reportType').value;
            const timeFields = document.getElementById('timeFields');
            const yearField = document.getElementById('yearField');
            const monthField = document.getElementById('monthField');
            const quarterField = document.getElementById('quarterField');
            const halfField = document.getElementById('halfField');
            
            // Hide all optional fields first
            monthField.classList.add('hidden');
            quarterField.classList.add('hidden');
            halfField.classList.add('hidden');
            
            // Clear required attributes and disable all selects initially
            const yearSelect = document.getElementById('yearSelect');
            const monthSelect = document.getElementById('monthSelect');
            const quarterSelect = document.getElementById('quarterSelect');
            const halfSelect = document.getElementById('halfSelect');

            yearSelect.removeAttribute('required');
            monthSelect.removeAttribute('required');
            quarterSelect.removeAttribute('required');
            halfSelect.removeAttribute('required');

            yearSelect.disabled = true;
            monthSelect.disabled = true;
            quarterSelect.disabled = true;
            halfSelect.disabled = true;
            
            if (reportType === 'overall' || reportType === '') {
                timeFields.classList.add('hidden');
                yearField.classList.add('hidden');
                // keep all selects disabled
            } else {
                timeFields.classList.remove('hidden');
                yearField.classList.remove('hidden');
                // enable year select and mark required
                yearSelect.disabled = false;
                yearSelect.setAttribute('required', 'required');

                // Show specific fields based on report type and enable them
                switch (reportType) {
                    case 'monthly':
                        monthField.classList.remove('hidden');
                        monthSelect.disabled = false;
                        monthSelect.setAttribute('required', 'required');
                        break;
                    case 'quarterly':
                        quarterField.classList.remove('hidden');
                        quarterSelect.disabled = false;
                        quarterSelect.setAttribute('required', 'required');
                        break;
                    case 'half_yearly':
                        halfField.classList.remove('hidden');
                        halfSelect.disabled = false;
                        halfSelect.setAttribute('required', 'required');
                        break;
                    default:
                        // yearly or other types only need year
                        break;
                }
            }
        }

        // Show report in modal
        function showReportModal() {
            const modal = document.getElementById('reportModal');
            modal.style.display = 'flex';
        }

        // Close report modal
        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
        }

        // Download report as PDF
        function downloadPDF() {
            const reportContent = document.getElementById('reportContent');
            
            // Create a new div for PDF generation
            const pdfContent = document.createElement('div');
            pdfContent.style.padding = '40px';
            pdfContent.style.backgroundColor = 'white';
            pdfContent.innerHTML = reportContent.innerHTML;
            
            // Add report title
            const title = document.createElement('h1');
            title.textContent = 'Financial Report';
            title.style.textAlign = 'center';
            title.style.color = '#2c3e50';
            title.style.marginBottom = '20px';
            pdfContent.prepend(title);
            
            // Add footer
            const footer = document.createElement('div');
            footer.style.textAlign = 'center';
            footer.style.marginTop = '40px';
            footer.style.paddingTop = '20px';
            footer.style.borderTop = '1px solid #ccc';
            footer.style.color = '#666';
            footer.style.fontSize = '12px';
            footer.innerHTML = 'Generated on ' + new Date().toLocaleDateString() + ' | ' + 
                              '<?php echo htmlspecialchars($office["business_name"]); ?>';
            pdfContent.appendChild(footer);
            
            // Use html2canvas to capture the content
            html2canvas(pdfContent, {
                scale: 2,
                useCORS: true,
                logging: false
            }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jspdf.jsPDF('p', 'mm', 'a4');
                    const imgWidth = 190;
                    const pageHeight = 280;
                    const imgHeight = canvas.height * imgWidth / canvas.width;

                    // Add first page
                    let position = 10;
                    pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);

                    // If the content is longer than one page, add pages slicing the image vertically
                    let heightLeft = imgHeight - pageHeight;
                    while (heightLeft > -pageHeight) {
                        // Calculate the position for the next slice
                        position = heightLeft - imgHeight + 10;
                        pdf.addPage();
                        pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;
                    }

                // Generate filename
                const reportType = document.getElementById('reportType').value;
                const reportCategory = document.querySelector('[name="report_category"]').value;
                const filename = 'financial_report_' + reportCategory + '_' + 
                               reportType + '_' + new Date().toISOString().slice(0,10) + '.pdf';
                
                pdf.save(filename);
            }).catch(error => {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again.');
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('reportModal');
            if (event.target == modal) {
                closeReportModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeReportModal();
            }
        });
    </script>
</body>
</html>