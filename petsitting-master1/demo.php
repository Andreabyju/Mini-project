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

            if ($user) {
                // Use password_verify instead of md5
                if (password_verify($password, $user['password'])) {
                    // Update session variables to match table structure
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
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
</body>
</html> 