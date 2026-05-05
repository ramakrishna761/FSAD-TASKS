<!DOCTYPE html>
<html>
<head>
    <title>Feedback Form</title>

    <script>
        function validate() {
            let name = document.getElementById("name").value;
            let email = document.getElementById("email").value;
            let msg = document.getElementById("message").value;

            if (name == "" || email == "" || msg == "") {
                alert("All fields required");
                return false;
            }

            if (!email.includes("@")) {
                alert("Invalid Email");
                return false;
            }

            return true;
        }

        function submitForm() {
            if (validate()) {
                document.getElementById("form").submit();
            }
        }
    </script>

    <style>
        body { font-family: Arial; }
        .box {
            width: 400px;
            margin: 80px auto;
            padding: 20px;
            border: 1px solid gray;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
        }
        button {
            padding: 10px;
            width: 100%;
        }
    </style>
</head>

<body>

<div class="box">
    <h2>Feedback Form</h2>

    <form id="form" action="submit.php" method="POST">
        <input type="text" name="name" id="name" placeholder="Name">
        <input type="text" name="email" id="email" placeholder="Email">
        <textarea name="message" id="message" placeholder="Message"></textarea>

        <!-- DOUBLE CLICK -->
        <button type="button" ondblclick="submitForm()">Double Click Submit</button>
    </form>
</div>

</body>
</html>