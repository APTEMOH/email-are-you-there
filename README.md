# email-are-you-there
PHP class for checking the existence of email

Example:

    include('CheckMail.php');
    $check = new CheckMail($email);
    if($check->execute()){
    //Email exists
    }else{
    //Email does not exist
    }
