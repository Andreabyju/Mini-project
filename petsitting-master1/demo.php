<?php
require_once "connect.php";
session_start();

// Initialize variables
$admin_email = "admin@gmail.com";
$admin_password = "admin1234";
$errors = [];

// Only check credentials if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if($username == $admin_email && $password == $admin_password){
        $_SESSION['username'] = $username;
        header("Location: admin_dashboard.php");
        exit();
    }
}

// Function to sanitize input data
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Process login when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (empty($_POST['email'])) {
        $errors['email'] = "Email is required";
    } else {
        $email = validateInput($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format";
        }
    }

    // Validate password
    if (empty($_POST['password'])) {
        $errors['password'] = "Password is required";
    } else {
        $password = validateInput($_POST['password']);
    }

    // If no validation errors, proceed with login
    if (empty($errors)) {
        try {
            // Updated query to use PDO prepared statement
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo $user;

            if ($user) {
                // Use password_verify instead of md5
                if (password_verify($password, $user['password'])) {
                    // Update session variables to match table structure
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Handle remember me (store only email, never store passwords in cookies)
                    if (isset($_POST['remember-me'])) {
                        setcookie("user_email", $email, time() + (86400 * 30), "/", "", true, true);
                    }

                    header("Location:hhh2.php");
                    exit();
                } else {
                    $errors['login'] = "Invalid email or password";
                }
            } else {
                $errors['login'] = "Invalid email or password";
            }
        } catch (Exception $e) {
            $errors['db'] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Canine & Feline Co.- Login</title>
<script src="main.js" defer type="module"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        .login-background {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/bg_3.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .form-container {
            background-color: rgba(255, 255, 255, 0.95);
        }
        /* Google Button Styles */
        .google-button {
            width: 100%;
            height: 42px;
            background-color: white;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .google-button:hover {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .google-icon-wrapper {
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 8px;
        }

        .google-icon {
            width: 18px;
            height: 18px;
        }

        .btn-text {
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: #757575;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Login Section -->
    <div class="login-background min-h-screen flex items-center justify-end py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-xl w-full form-container rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 sign-in-text">Login</h2>
                <p class="mt-2 text-gray-600 sign-in-text">Please sign in to your account</p>
                <?php if (isset($errors['login'])): ?>
                    <p class="mt-2 text-red-600"><?php echo $errors['login']; ?></p>
                <?php endif; ?>
            </div>
            
            <form class="space-y-6" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                    <input type="email" id="email" name="email" 
                        autocomplete="new-password"
                        readonly
                        onfocus="this.removeAttribute('readonly');"
                        placeholder="Enter your email"
                        class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black 
                        <?php echo isset($errors['email']) ? 'border-red-500' : ''; ?>">
                    <?php if (isset($errors['email'])): ?>
                        <p class="mt-1 text-red-500 text-sm"><?php echo $errors['email']; ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password"
                        autocomplete="new-password"
                        readonly
                        onfocus="this.removeAttribute('readonly');"
                        placeholder="Enter your password"
                        class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black
                        <?php echo isset($errors['password']) ? 'border-red-500' : ''; ?>">
                    <?php if (isset($errors['password'])): ?>
                        <p class="mt-1 text-red-500 text-sm"><?php echo $errors['password']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember-me" name="remember-me" 
                            class="h-4 w-4 text-black focus:ring-black border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-700">Remember me</label>
                    </div>
                    <a href="fp.php" class="text-sm text-gray-600 hover:text-gray-900 forgot-password">Forgot password?</a>
                </div>

                <button type="submit" 
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                    Sign In
                </button>
            </form>
            
            <div class="relative my-4">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">Or</span>
                </div>
            </div>
            
            <!-- Remove the form tags and keep just the button -->
            <button type="button" id="google-login-btn" class="google-button">
                <div class="google-icon-wrapper">
                    <svg class="google-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <g transform="matrix(1, 0, 0, 1, 27.009001, -39.238998)">
                            <path fill="#4285F4" d="M -3.264 51.509 C -3.264 50.719 -3.334 49.969 -3.454 49.239 L -14.754 49.239 L -14.754 53.749 L -8.284 53.749 C -8.574 55.229 -9.424 56.479 -10.684 57.329 L -10.684 60.329 L -6.824 60.329 C -4.564 58.239 -3.264 55.159 -3.264 51.509 Z"/>
                            <path fill="#34A853" d="M -14.754 63.239 C -11.514 63.239 -8.804 62.159 -6.824 60.329 L -10.684 57.329 C -11.764 58.049 -13.134 58.489 -14.754 58.489 C -17.884 58.489 -20.534 56.379 -21.484 53.529 L -25.464 53.529 L -25.464 56.619 C -23.494 60.539 -19.444 63.239 -14.754 63.239 Z"/>
                            <path fill="#FBBC05" d="M -21.484 53.529 C -21.734 52.809 -21.864 52.039 -21.864 51.239 C -21.864 50.439 -21.724 49.669 -21.484 48.949 L -21.484 45.859 L -25.464 45.859 C -26.284 47.479 -26.754 49.299 -26.754 51.239 C -26.754 53.179 -26.284 54.999 -25.464 56.619 L -21.484 53.529 Z"/>
                            <path fill="#EA4335" d="M -14.754 43.989 C -12.984 43.989 -11.404 44.599 -10.154 45.789 L -6.734 42.369 C -8.804 40.429 -11.514 39.239 -14.754 39.239 C -19.444 39.239 -23.494 41.939 -25.464 45.859 L -21.484 48.949 C -20.534 46.099 -17.884 43.989 -14.754 43.989 Z"/>
                        </g>
                    </svg>
                </div>
                <span class="btn-text">Sign in with Google</span>
            </button>

            <p class="mt-6 text-center text-sm text-gray-600 not-member">
                Not a member yet? 
                <a href="registration.php" class="font-medium text-black hover:text-gray-800">Register now</a>
            </p>
        </div>
    </div>

    <?php if (isset($errors['db'])): ?>
        <script>
            alert('<?php echo $errors['db']; ?>');
        </script>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Add validation styling
        const validationStyles = `
            <style>
                .is-valid {
                    border-color: #10b981 !important;
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2310b981' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
                    background-repeat: no-repeat;
                    background-position: right calc(0.375em + 0.1875rem) center;
                    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
                    padding-right: calc(1.5em + 0.75rem) !important;
                }
                
                .is-invalid {
                    border-color: #ef4444 !important;
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23ef4444'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23ef4444' stroke='none'/%3e%3c/svg%3e");
                    background-repeat: no-repeat;
                    background-position: right calc(0.375em + 0.1875rem) center;
                    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
                    padding-right: calc(1.5em + 0.75rem) !important;
                }
                
                .validation-message {
                    font-size: 0.875rem;
                    margin-top: 0.25rem;
                }
                
                .invalid-feedback {
                    color: #ef4444;
                    display: none;
                }
                
                .valid-feedback {
                    color: #10b981;
                    display: none;
                }
            </style>
        `;
        
        $('head').append(validationStyles);
        
        // Email validation
        $('#email').on('input blur', function() {
            validateEmail($(this));
        });
        
        // Password validation
        $('#password').on('input blur', function() {
            validatePassword($(this));
        });
        
        // Validate form on submit
        $('form').on('submit', function(e) {
            const emailValid = validateEmail($('#email'));
            const passwordValid = validatePassword($('#password'));
            
            if (!emailValid || !passwordValid) {
                e.preventDefault();
            }
        });
        
        // Email validation function
        function validateEmail(element) {
            const email = element.val().trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            // Remove any existing feedback elements
            element.removeClass('is-valid is-invalid');
            element.next('.validation-message').remove();
            
            if (email === '') {
                element.addClass('is-invalid');
                element.after('<p class="validation-message invalid-feedback" style="display: block;">Email is required</p>');
                return false;
            } else if (!emailRegex.test(email)) {
                element.addClass('is-invalid');
                element.after('<p class="validation-message invalid-feedback" style="display: block;">Invalid email format</p>');
                return false;
            } else {
                element.addClass('is-valid');
                element.after('<p class="validation-message valid-feedback" style="display: block;">Looks good!</p>');
                return true;
            }
        }
        
        // Password validation function
        function validatePassword(element) {
            const password = element.val();
            
            // Remove any existing feedback elements
            element.removeClass('is-valid is-invalid');
            element.next('.validation-message').remove();
            
            if (password === '') {
                element.addClass('is-invalid');
                element.after('<p class="validation-message invalid-feedback" style="display: block;">Password is required</p>');
                return false;
            } else if (password.length < 6) {
                element.addClass('is-invalid');
                element.after('<p class="validation-message invalid-feedback" style="display: block;">Password must be at least 6 characters</p>');
                return false;
            } else {
                element.addClass('is-valid');
                element.after('<p class="validation-message valid-feedback" style="display: block;">Looks good!</p>');
                return true;
            }
        }
    });
    </script>
</body>
</html> 