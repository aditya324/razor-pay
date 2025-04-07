<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!file_exists('config.php')) {
    die("Config file missing.");
}
require('config.php');

if (!isset($username) || !isset($password)) {
    die("SMTP username/password missing in config.php.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment Failed</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .container {
      max-width: 700px;
      margin-top: 30px;
      margin-bottom: 50px;
    }
    .alert-box {
      border-left: 5px solid #dc3545;
      border-radius: 0;
    }
    .summary-table th {
      width: 30%;
      background-color: #f1f1f1;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card shadow">
      <div class="card-header bg-danger text-white">
        <h2 class="mb-0"><i class="bi bi-x-circle-fill"></i> Payment Failed</h2>
      </div>
      <div class="card-body">
        <div class="alert alert-danger alert-box">
          <h4 class="alert-heading">Oops! Payment Unsuccessful</h4>
          <p>We encountered an issue processing your payment. Please try again or contact our support team for assistance.</p>
          <hr>
          <p class="mb-0">Your order has not been confirmed. No amount has been deducted from your account.</p>
        </div>

        <div id="emailError" class="alert alert-warning d-none">
          <small>Note: We couldn't notify the admin about this failure. Please contact support.</small>
        </div>

        <h4 class="mt-4 mb-3">Order Summary</h4>
        <table class="table table-bordered summary-table">
          <tbody id="summaryBody">
            <tr>
              <td colspan="2">Loading...</td>
            </tr>
          </tbody>
        </table>

        <div class="d-grid gap-2 d-md-flex justify-content-md-between mt-4">
          <a href="payment.php" class="btn btn-primary btn-lg px-4 me-md-2">
            <i class="bi bi-arrow-repeat"></i> Try Payment Again
          </a>
          <a href="contact.php" class="btn btn-outline-secondary btn-lg px-4">
            <i class="bi bi-headset"></i> Contact Support
          </a>
        </div>
      </div>
      <div class="card-footer text-muted">
        <small>For any queries, please email us at <?php echo htmlspecialchars($username); ?></small>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const data = JSON.parse(sessionStorage.getItem("paymentFailure") || '{}');
      const summaryBody = document.getElementById("summaryBody");

      if (!data || !data.order_id) {
        summaryBody.innerHTML = `<tr><td colspan="2">No payment data found.</td></tr>`;
        return;
      }

      const formatRow = (label, value) => `<tr><th>${label}</th><td>${value}</td></tr>`;
      summaryBody.innerHTML =
        formatRow("Order ID", data.order_id) +
        formatRow("Name", data.name) +
        formatRow("Email", data.email) +
        formatRow("Phone", data.phone) +
        formatRow("Address", data.address) +
        formatRow("Course", data.course) +
        formatRow("Amount", `₹${(data.amount / 100).toFixed(2)}`) +
        formatRow("Mode", data.mode);

      // Send to backend for email
      fetch("failure-mail.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
      })
      .then(res => {
        if (!res.ok) throw new Error("Email failed");
      })
      .catch(() => {
        document.getElementById("emailError").classList.remove("d-none");
      });

      sessionStorage.removeItem("paymentFailure");
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
