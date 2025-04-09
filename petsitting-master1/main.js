import { initializeApp } from "https://www.gstatic.com/firebasejs/11.5.0/firebase-app.js";
import { getAuth, GoogleAuthProvider, signInWithPopup } from "https://www.gstatic.com/firebasejs/11.5.0/firebase-auth.js";
const firebaseConfig = {
  apiKey: "AIzaSyCEQ3zMF716VyBg5F_NGmWe3mTnCwX00aU",
  authDomain: "login-f88c7.firebaseapp.com",
  projectId: "login-f88c7",
  storageBucket: "login-f88c7.firebasestorage.app",
  messagingSenderId: "770392962152",
  appId: "1:770392962152:web:3a30a09cf84c4bd52b37dd"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
auth.languageCode = 'en';
const provider = new GoogleAuthProvider();

const googleLogin = document.getElementById("google-login-btn");
googleLogin.addEventListener("click", function(e) {
    e.preventDefault(); // Prevent any default form submission
    
    signInWithPopup(auth, provider)
    .then((result) => {
        const credential = GoogleAuthProvider.credentialFromResult(result);
        const user = result.user;
        console.log(user);
        
        // Send user data to your server before redirecting
        fetch('google_auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: user.email,
                name: user.displayName,
                uid: user.uid
            })
        })
        .then(response => response.json())
        .then(data => {
            window.location.href = "hhh2.php";
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to authenticate with server');
        });

    }).catch((error) => {
        console.error('Firebase Error:', error);
        alert('Failed to sign in with Google: ' + error.message);
    });
});