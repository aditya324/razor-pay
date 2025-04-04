<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>One Click Academy - Digital Marketing Course Enrollment</title>
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>



  <!-- CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/react-hot-toast@4.3.3/dist/toast.css">

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/react-hot-toast@4.3.3/dist/toast.umd.js"></script>
</head>

<body class="bg-gradient-to-r from-blue-100 to-indigo-100 px-5 lg:px-0">
  <div
    class="max-w-4xl w-full mx-auto mt-10 bg-white shadow-lg rounded-lg overflow-hidden">
    <div class="p-6 border-b flex flex-col items-center">
      <img
        src="./images/oneclicklogo.png"
        alt="One Click Academy Logo"
        class="w-auto h-auto object-contain" />
      <h1 class="text-2xl font-bold text-gray-800 mt-4">One Click Academy</h1>
      <p class="text-gray-600">Digital Marketing Course Enrollment</p>
    </div>

    <div class="flex border-b">
      <button
        id="userTab"
        class="w-1/2 py-3 text-blue-500 font-semibold border-b-2 border-blue-500">
        Your Details
      </button>
      <button
        id="paymentTabBtn"
        class="w-1/2 py-3 text-gray-500 hover:text-blue-500 font-semibold">
        Course Info
      </button>
    </div>

    <form id="paymentForm" class="px-4 sm:px-6 lg:px-8 py-6">
      <div id="userInfoTab">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="firstName" class="block text-gray-700 font-medium">First Name</label>
            <input
              type="text"
              id="firstName"
              name="firstName"
              required
              class="w-full p-3 mt-1 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label for="lastName" class="block text-gray-700 font-medium">Last Name</label>
            <input
              type="text"
              id="lastName"
              name="lastName"
              required
              class="w-full p-3 mt-1 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
          <div>
            <label for="email" class="block text-gray-700 font-medium">Email</label>
            <input
              type="email"
              id="email"
              name="email"
              required
              class="w-full p-3 mt-1 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label for="phone" class="block text-gray-700 font-medium">Phone</label>
            <input
              type="tel"
              id="phone"
              name="phone"
              required
              class="w-full p-3 mt-1 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
          <div>
            <label for="city" class="block text-gray-700 font-medium">City</label>
            <input
              type="text"
              id="city"
              name="city"
              required
              class="w-full p-3 mt-1 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label for="address" class="block text-gray-700 font-medium">Address</label>
            <textarea
              id="address"
              name="address"
              required
              rows="3"
              class="w-full p-3 mt-1 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
          </div>
        </div>

        <!-- New Dropdown for mode selection -->
        <div class="mt-6">
          <label for="mode" class="block text-gray-700 font-medium">Select Mode:</label>
          <select
            id="mode"
            name="mode"
            class="w-full p-3 mt-1 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="online">Online</option>
            <option value="offline">Offline</option>
          </select>
        </div>

        <button
          type="button"
          id="toPaymentTab"
          class="mt-6 w-full bg-[#FF7733] text-white py-3 rounded">
          Next
        </button>
      </div>

      <div id="paymentInfoTab" class="hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
          Digital Marketing Course
        </h2>
        <p class="mt-2 text-gray-600">
          Join One Click Academy and unlock the secrets of digital marketing.
          Our comprehensive course covers SEO, Social Media Marketing, Email
          Marketing, PPC, and more.
        </p>
        <ul class="mt-4 text-gray-700 list-disc list-inside">
          <li>6 Weeks Comprehensive Curriculum</li>
          <li>Expert Instructors</li>
          <li>Hands-On Projects</li>
          <li>Certification on Completion</li>
        </ul>

        <!-- New Dropdown for mode selection -->


        <div class="mt-6">
          <label class="block text-gray-700 font-medium">Course Fee:</label>
          <div class="mt-2 p-4 bg-gray-100 rounded shadow">
            <span
              id="courseFee"
              class="text-3xl font-bold text-blue-600"></span>
          </div>
        </div>
        <button
          type="submit"
          class="mt-6 w-full bg-[#FF7733] text-white py-3 rounded">
          Proceed to Pay
        </button>
      </div>
    </form>
  </div>

  <!-- Script to update fee based on selected mode -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const courseFeeElement = document.getElementById("courseFee");
      const modeElement = document.getElementById("mode");

      function updateFee() {
        if (modeElement.value === "online") {
          courseFeeElement.textContent = "₹60,000";
        } else {
          courseFeeElement.textContent = "₹80,000";
        }
      }

      modeElement.addEventListener("change", updateFee);
      updateFee();
    });
  </script>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Example tab switching logic (if applicable)
      const userTab = document.getElementById("userTab");
      const paymentTabBtn = document.getElementById("paymentTabBtn");
      const userInfoTab = document.getElementById("userInfoTab");
      const paymentInfoTab = document.getElementById("paymentInfoTab");
      const toPaymentTab = document.getElementById("toPaymentTab");

      if (toPaymentTab) {
        toPaymentTab.addEventListener("click", function() {
          if (!validateUserInfo()) {
            alert("Please fill in all required fields correctly.");
            return;
          }
          userInfoTab.classList.add("hidden");
          paymentInfoTab.classList.remove("hidden");
          userTab.classList.remove("border-b-2", "border-blue-500", "text-blue-500");
          paymentTabBtn.classList.add("border-b-2", "border-blue-500", "text-blue-500");
        });
      }

      userTab.addEventListener("click", function() {
        userInfoTab.classList.remove("hidden");
        paymentInfoTab.classList.add("hidden");
        userTab.classList.add("border-b-2", "border-blue-500", "text-blue-500");
        paymentTabBtn.classList.remove("border-b-2", "border-blue-500", "text-blue-500");
      });

      paymentTabBtn.addEventListener("click", function() {
        if (!validateUserInfo()) {
          alert("Please fill in all required fields before proceeding to payment.");
          return;
        }
        userInfoTab.classList.add("hidden");
        paymentInfoTab.classList.remove("hidden");
        paymentTabBtn.classList.add("border-b-2", "border-blue-500", "text-blue-500");
        userTab.classList.remove("border-b-2", "border-blue-500", "text-blue-500");
      });

      // Payment form submission handler
      document.getElementById("paymentForm").addEventListener("submit", function(e) {
        e.preventDefault();

        if (!validateUserInfo()) {
          alert("Please fill in all required fields correctly.");
          return;
        }

        // Get form values
        const firstName = document.getElementById("firstName").value;
        const lastName = document.getElementById("lastName").value;
        const email = document.getElementById("email").value;
        const phone = document.getElementById("phone").value;
        const city = document.getElementById("city").value;
        const address = document.getElementById("address").value;
        const course = document.getElementById("course") ? document.getElementById("course").value : "DefaultCourse";
        const mode = document.getElementById("mode").value; // online or offline

        // Set amount based on mode: online => ₹60,000, offline => ₹80,000 (in paisa)
        const amount = mode === "online" ? 6000000 : 8000000;
        console.log("Amount:", amount);
        // Create order by sending details to order.php
        fetch("order.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-API-KEY": "Hbf7IwNppbLlKLsVbIBpmyJo" // Ensure this matches what your server expects
            },
            body: JSON.stringify({
              name: firstName + " " + lastName,
              email: email,
              phone: phone,
              city: city,
              address: address,
              amount: amount,
              mode: mode
            })
          })
          .then(response => response.json())
          .then(orderData => {
            // orderData is expected to have properties like id and amount.

            console.log(orderData)
            const options = {
              key: "rzp_test_3i3aUje3qXaxGE",
              amount: orderData.amount,
              currency: "INR",
              name: "One Click Academy",
              description: "Digital Marketing Course Enrollment",
              order_id: orderData.id,
              handler: function(response) {
                // Payment successful handler: verify the payment on your server
                fetch("verify.php", {
                    method: "POST",
                    headers: {
                      "Content-Type": "application/json",
                      "X-API-KEY": "Hbf7IwNppbLlKLsVbIBpmyJo"
                    },
                    body: JSON.stringify({
                      payment_id: response.razorpay_payment_id,
                      order_id: response.razorpay_order_id,
                      signature: response.razorpay_signature,
                      name: firstName + " " + lastName,
                      email: email,
                      phone: phone,
                      city: city,
                      address: address,
                      course: course,
                      amount: amount,
                      mode: mode
                    })
                  })
                  .then(res => res.json())
                  .then(data => {

                    console.log(data);
                    sessionStorage.setItem('orderResponse', JSON.stringify(data.data));

                    alert(data.message);
                    if (data.redirect) {
                      try {
                        const params = new URLSearchParams(data.data).toString();
                        window.location.href = `${data.redirect}`;
                      } catch (e) {
                        console.error("Redirect failed:", e);
                      }
                    }
                  });
              },
              prefill: {
                name: firstName + " " + lastName,
                email: email,
                contact: phone
              },
              theme: {
                color: "#3399cc"
              },
              modal: {
                ondismiss: function() {
                  // When the Razorpay widget is closed, ask the user if they want to cancel the payment.
                  if (window.confirm("Do you want to quit payment?")) {
                    // Build query parameters for failure.php
                    const params = new URLSearchParams({
                      order_id: orderData.id,
                      name: firstName + " " + lastName,
                      email: email,
                      phone: phone,
                      city: city,
                      address: address,
                      course: course,
                      amount: amount
                    });
                    window.location.href = "failure.php?" + params.toString();
                  }
                }
              }
            };

            const rzp1 = new Razorpay(options);
            rzp1.open();
          })
          .catch(error => {
            console.error("Error creating order:", error);
          });
      });
    });
  </script>


  <script>
    function validateUserInfo() {
      const firstName = document.getElementById("firstName").value.trim();
      const lastName = document.getElementById("lastName").value.trim();
      const email = document.getElementById("email").value.trim();
      const phone = document.getElementById("phone").value.trim();
      const city = document.getElementById("city").value.trim();
      const address = document.getElementById("address").value.trim();

      let isValid = true;
      document
        .querySelectorAll(".error-message")
        .forEach((el) => el.remove());

      if (!firstName) {
        showError("firstName", "First Name is required.");
        isValid = false;
      }

      if (!lastName) {
        showError("lastName", "Last Name is required.");
        isValid = false;
      }

      if (!email) {
        showError("email", "Email is required.");
        isValid = false;
      } else if (!validateEmail(email)) {
        showError("email", "Please enter a valid email address.");
        isValid = false;
      }

      if (!phone) {
        showError("phone", "Phone is required.");
        isValid = false;
      } else if (!validatePhone(phone)) {
        showError("phone", "Please enter a valid phone number.");
        isValid = false;
      }

      if (!city) {
        showError("city", "City is required.");
        isValid = false;
      }

      if (!address) {
        showError("address", "Address is required.");
        isValid = false;
      }

      return isValid;
    }

    function showError(fieldId, message) {
      const field = document.getElementById(fieldId);
      const errorMessage = document.createElement("div");
      errorMessage.className = "error-message text-red-500 text-sm mt-1";
      errorMessage.textContent = message;
      field.parentNode.appendChild(errorMessage);
      field.classList.add("border-red-500");
    }

    function validateEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    }

    function validatePhone(phone) {
      const phoneRegex = /^\d{10}$/;
      return phoneRegex.test(phone);
    }
  </script>
</body>

</html>