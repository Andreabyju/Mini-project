<?php
require_once "connect.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $pincode = trim($_POST['pincode']);
    $pet_name = trim($_POST['pet_name']);
    $pet_age = trim($_POST['pet_age']);
    $pet_type = $_POST['pet_type'] ?? '';
    $pet_breed = trim($_POST['pet_breed']);
    $password = trim($_POST['password']);

    // Server-side validation
    $errors = [];

    if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        $errors[] = 'Name should only contain letters and spaces';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Phone number must be 10 digits';
    }

    if (!preg_match('/^[0-9]{6}$/', $pincode)) {
        $errors[] = 'Pincode must be 6 digits';
    }

    if (!is_numeric($pet_age) || $pet_age < 1 || $pet_age > 50) {
        $errors[] = 'Pet age must be between 1 and 50';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }

    if (empty($errors)) {
        try {
            // Generate username from email
            $username = strstr($email, '@', true);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Prepare SQL query
            $sql = "INSERT INTO users (name, username, password, email, phone_number, address, pincode, 
                    pet_name, pet_age, pet_type, pet_breed) 
                    VALUES (:name, :username, :password, :email, :phone, :address, :pincode, 
                    :pet_name, :pet_age, :pet_type, :pet_breed)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':username' => $username,
                ':password' => $hashed_password,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address,
                ':pincode' => $pincode,
                ':pet_name' => $pet_name,
                ':pet_age' => $pet_age,
                ':pet_type' => $pet_type,
                ':pet_breed' => $pet_breed
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Registration successful! Your username is: ' . $username]);
            exit();
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()]);
            exit();
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Sitter Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('images/image_6.jpg'); /* Replace with your image path */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            padding: 40px 0;
            min-height: 100vh;
        }
        .form-container {
            background-color: rgba(255, 255, 255, 0.95); /* Semi-transparent white */
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            max-width: 800px;
            margin: 0 auto;
            backdrop-filter: blur(10px); /* Adds a blur effect behind the form */
        }
        .form-title {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-weight: bold;
        }
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        .error-message {
            color: red;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        /* Optional: Add a dark overlay to make form more readable */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.3); /* Dark overlay */
            z-index: -1;
        }
        /* Make form inputs slightly transparent */
        .form-control {
            background-color: rgba(255, 255, 255, 0.9);
        }
        /* Hover effect for form container */
        .form-container:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
        /* Style the submit button */
        .btn-primary {
            background-color: #000000;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #333333; /* Slightly lighter black on hover */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Register Here !</h2>
            <form id="registrationForm" method="POST" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label required-field">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
                        <div class="error-message" id="nameError"></div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label required-field">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required
                               pattern="[0-9]{10}" title="Please enter 10 digits">
                        <div class="error-message" id="phoneError"></div>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label for="email" class="form-label required-field">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               autocomplete="off">
                        <div class="error-message" id="emailError"></div>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label for="address" class="form-label required-field">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                        <div class="error-message" id="addressError"></div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="pincode" class="form-label required-field">Pincode</label>
                        <input type="text" class="form-control" id="pincode" name="pincode" required
                               pattern="[0-9]{6}" title="Please enter 6 digits">
                        <div class="error-message" id="pincodeError"></div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label required-field">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required
                               minlength="8" title="Password must be at least 8 characters long"
                               autocomplete="new-password">
                        <div class="error-message" id="passwordError"></div>
                    </div>

                    <div class="col-12">
                        <hr>
                        <h4 class="mb-3">Pet Information</h4>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="pet_name" class="form-label required-field">Pet Name</label>
                        <input type="text" class="form-control" id="pet_name" name="pet_name" required
                               pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
                        <div class="error-message" id="petNameError"></div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="pet_age" class="form-label required-field">Pet Age</label>
                        <input type="number" class="form-control" id="pet_age" name="pet_age" required
                               min="1" max="50">
                        <div class="error-message" id="petAgeError"></div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Pet Type</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pet_type" id="dog" value="dog" required>
                            <label class="form-check-label" for="dog">Dog</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pet_type" id="cat" value="cat">
                            <label class="form-check-label" for="cat">Cat</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pet_type" id="bird" value="bird">
                            <label class="form-check-label" for="bird">Bird</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pet_type" id="other" value="other">
                            <label class="form-check-label" for="other">Other</label>
                        </div>
                        <div class="error-message" id="petTypeError"></div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="pet_breed" class="form-label required-field">Pet Breed</label>
                        <input type="text" class="form-control" id="pet_breed" name="pet_breed" required
                               pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
                        <div class="error-message" id="petBreedError"></div>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100 py-2">Register</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            function showError(elementId, message) {
                $(`#${elementId}`).text(message).show();
            }

            function hideError(elementId) {
                $(`#${elementId}`).hide();
            }

            function validateForm() {
                let isValid = true;

                // Name validation
                const name = $('#name').val().trim();
                if (!name) {
                    showError('nameError', 'Name is required');
                    isValid = false;
                } else if (!/^[A-Za-z\s]+$/.test(name)) {
                    showError('nameError', 'Name should only contain letters and spaces');
                    isValid = false;
                } else {
                    hideError('nameError');
                }

                // Email validation
                const email = $('#email').val().trim();
                if (!email) {
                    showError('emailError', 'Email is required');
                    isValid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    showError('emailError', 'Please enter a valid email address');
                    isValid = false;
                } else {
                    hideError('emailError');
                }

                // Phone validation
                const phone = $('#phone').val().trim();
                if (!phone) {
                    showError('phoneError', 'Phone number is required');
                    isValid = false;
                } else if (!/^[0-9]{10}$/.test(phone)) {
                    showError('phoneError', 'Please enter a valid 10-digit phone number');
                    isValid = false;
                } else {
                    hideError('phoneError');
                }

                // Address validation
                const address = $('#address').val().trim();
                if (!address) {
                    showError('addressError', 'Address is required');
                    isValid = false;
                } else {
                    hideError('addressError');
                }

                // Pincode validation
                const pincode = $('#pincode').val().trim();
                if (!pincode) {
                    showError('pincodeError', 'Pincode is required');
                    isValid = false;
                } else if (!/^[0-9]{6}$/.test(pincode)) {
                    showError('pincodeError', 'Please enter a valid 6-digit pincode');
                    isValid = false;
                } else {
                    hideError('pincodeError');
                }

                // Pet name validation
                const petName = $('#pet_name').val().trim();
                if (!petName) {
                    showError('petNameError', 'Pet name is required');
                    isValid = false;
                } else if (!/^[A-Za-z\s]+$/.test(petName)) {
                    showError('petNameError', 'Pet name should only contain letters and spaces');
                    isValid = false;
                } else {
                    hideError('petNameError');
                }

                // Pet age validation
                const petAge = $('#pet_age').val();
                if (!petAge) {
                    showError('petAgeError', 'Pet age is required');
                    isValid = false;
                } else if (petAge < 1 || petAge > 50) {
                    showError('petAgeError', 'Pet age must be between 1 and 50');
                    isValid = false;
                } else {
                    hideError('petAgeError');
                }

                // Pet type validation
                if (!$('input[name="pet_type"]:checked').val()) {
                    showError('petTypeError', 'Please select a pet type');
                    isValid = false;
                } else {
                    hideError('petTypeError');
                }

                // Pet breed validation
                const petBreed = $('#pet_breed').val().trim();
                if (!petBreed) {
                    showError('petBreedError', 'Pet breed is required');
                    isValid = false;
                } else if (!/^[A-Za-z\s]+$/.test(petBreed)) {
                    showError('petBreedError', 'Pet breed should only contain letters and spaces');
                    isValid = false;
                } else {
                    hideError('petBreedError');
                }

                // Password validation
                const password = $('#password').val();
                if (!password) {
                    showError('passwordError', 'Password is required');
                    isValid = false;
                } else if (password.length < 8) {
                    showError('passwordError', 'Password must be at least 8 characters long');
                    isValid = false;
                } else {
                    hideError('passwordError');
                }

                return isValid;
            }

            $('#registrationForm').on('submit', function(e) {
                e.preventDefault();
                
                if (validateForm()) {
                    const formData = new FormData(this);
                    formData.append('submit', '1');

                    $.ajax({
                        url: window.location.pathname,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            try {
                                const result = JSON.parse(response);
                                if (result.status === 'success') {
                                    alert(result.message);
                                    window.location.href = 'demo.php';
                                } else {
                                    alert(result.message || 'Registration failed. Please try again.');
                                }
                            } catch (e) {
                                console.error(e);
                                alert('An error occurred. Please try again.');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText); // Log the response text for debugging
                            alert('An error occurred. Please try again.');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
