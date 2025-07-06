
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>BANTUAN PELAJARAN YPJ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="images/favicon.png" type="image/x-icon">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-image: url('images/bg2.jpg'); background-size: cover; background-position: center; background-attachment: fixed; height: 100vh;">
    <div class="container pt-5">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow-lg">
                    <div class="card-header text-white bg-primary">
                        <h5 class="mb-0">SEMAKAN PERMOHONAN</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="form-group">
                                <input type="text" class="form-control form-control-line" id="mykad" 
           placeholder="Sila masukkan nombor kad pengenalan pelajar" name="mykad" 
           minlength="14" maxlength="14" required 
           oninput="formatMyKad(this)" pattern="\d{6}-\d{2}-\d{4}">
</div>

<script>
function formatMyKad(input) {
    let value = input.value.replace(/\D/g, ''); // Remove non-numeric characters
    if (value.length > 6) value = value.slice(0, 6) + '-' + value.slice(6);
    if (value.length > 9) value = value.slice(0, 9) + '-' + value.slice(9);
    input.value = value.slice(0, 14); // Limit to 14 characters including dashes
}
</script>
                            </div>
                            <button type="submit" name="semak" class="btn btn-primary btn-block">Semak</button>
                        </form>

                                            </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
