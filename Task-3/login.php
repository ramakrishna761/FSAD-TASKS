<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body {
            font-family: Arial;
            background: linear-gradient(to right, #36d1dc, #5b86e5);
        }
        .box {
            width: 350px;
            margin: 100px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }
        button {
            width: 100%;
            padding: 10px;
            background: blue;
            color: white;
            border: none;
        }
        .error {
            color: red;
            font-size: 14px;
        }
    </style>

    <script>
        function validateForm() {
            let user = document.forms["loginForm"]["username"].value;
            let pass = document.forms["loginForm"]["password"].value;

            if (user == "" || pass == "") {
                document.getElementById("error").innerText = "All fields are required!";
                return false;
            }

            if (pass.length < 4) {
                document.getElementById("error").innerText = "Password must be at least 4 characters!";
                return false;
            }

            return true;
        }
    </script>
</head>
<body>

<div class="box">
    <h2>Login</h2>

    <form name="loginForm" action="process.php" method="POST" onsubmit="return validateForm()">
        <input type="text" name="username" placeholder="Username">
        <input type="password" name="password" placeholder="Password">

        <div id="error" class="error"></div>

        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>