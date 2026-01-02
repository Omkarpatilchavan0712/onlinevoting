<?php
session_start();
$conn = mysqli_connect("localhost","root","","voting");
if(!$conn) die("DB Error");

/* REGISTER */
if(isset($_POST['register'])){
 $name=$_POST['name'];
 $email=$_POST['email'];
 $pass=md5($_POST['password']);
 mysqli_query($conn,"INSERT INTO users(name,email,password) VALUES('$name','$email','$pass')");
 
 // Registration success popup
 echo "<script>alert('Registration Successful! You can now login.');</script>";
}

/* LOGIN */
if(isset($_POST['login'])){
 $email=$_POST['email'];
 $pass=md5($_POST['password']);
 $q=mysqli_query($conn,"SELECT * FROM users WHERE email='$email' AND password='$pass'");
 if(mysqli_num_rows($q)>0){
   $_SESSION['user']=mysqli_fetch_assoc($q);
   
   // Login success popup
   echo "<script>alert('Login Successful! Welcome {$_SESSION['user']['name']}');</script>";
 } else {
   // Wrong password popup
   echo "<script>alert('Incorrect email or password. Please try again.');</script>";
 }
}

/* LOGOUT */
if(isset($_GET['logout'])){
 session_destroy();
 header("Location:index.php");
}

/* VOTE */
if(isset($_POST['vote'])){
 $cid=$_POST['candidate'];
 $uid=$_SESSION['user']['id'];
 mysqli_query($conn,"UPDATE candidates SET votes=votes+1 WHERE id=$cid");
 mysqli_query($conn,"UPDATE users SET voted=1 WHERE id=$uid");
 $_SESSION['user']['voted']=1;
}

/* LIVE GRAPH DATA */
$labels=[]; $votes=[];
$r=mysqli_query($conn,"SELECT name,votes FROM candidates");
while($row=mysqli_fetch_assoc($r)){
 $labels[]=$row['name'];
 $votes[]=$row['votes'];
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>College Online Voting</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI'}

/* ✅ Professional Static Background */
body {
    min-height: 100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: linear-gradient(135deg, #3a6186, #89253e); /* professional gradient */
    background-size: cover;
    background-repeat: no-repeat;
    background-attachment: fixed;
}

body::before {
    content: "";
    position: fixed;
    top:0; left:0;
    width:100%;
    height:100%;
    background-image: radial-gradient(rgba(255,255,255,0.02) 1px, transparent 1px);
    background-size: 50px 50px;
    z-index: 0;
}

/* Card styles */
.card{
width:95%;max-width:420px;
background:rgba(255,255,255,.2);
backdrop-filter:blur(15px);
padding:30px;border-radius:20px;
box-shadow: 0 10px 30px rgba(0,0,0,0.3);
position: relative;
z-index: 1;
}

h1,h2{text-align:center;color:#fff;margin-bottom:15px}
input,button{
width:100%;padding:12px;margin:8px 0;
border:none;border-radius:8px;
}
button{
background:linear-gradient(135deg,#6a11cb,#2575fc);
color:white;font-weight:bold;cursor:pointer;
transition:0.3s;
}
button:hover {
  box-shadow: 0 0 15px #fff,
              0 0 25px #6a11cb,
              0 0 35px #2575fc;
  transform: scale(1.05);
}
input:focus {
  outline: none;
  box-shadow: 0 0 10px #48dbfb;
  border: 2px solid #48dbfb;
}

.link,.back{text-align:center;margin-top:12px}
a{color:white;text-decoration:none}

/* Voting UI */
.vote-container{margin-top:15px}
.vote-card{
background:rgba(255,255,255,0.18);
border-radius:16px;
padding:15px;
margin-bottom:12px;
display:flex;
align-items:center;
justify-content:space-between;
cursor:pointer;
transition:0.4s;
border:2px solid transparent;
}
.vote-card:hover{
transform:translateY(-6px) scale(1.03);
border:2px solid #00f2fe;
box-shadow:0 15px 35px rgba(0,0,0,0.4),0 0 15px #00f2fe;
}
.vote-card input{display:none}
.vote-card span{
color:white;
font-size:16px;
font-weight:600;
}
.custom-radio{
width:22px;height:22px;
border-radius:50%;
border:2px solid #fff;
position:relative;
}
.vote-card input:checked + .custom-radio{
border-color:#00f2fe;
box-shadow:0 0 10px #00f2fe;
}
.vote-card input:checked + .custom-radio::after{
content:''; width:10px;height:10px;
background:#00f2fe;
border-radius:50%;
position:absolute;
top:50%;left:50%;
transform:translate(-50%,-50%);
}
</style>
</head>

<body>

<div class="card">

<?php if(!isset($_SESSION['user'])){ ?>

<?php if(!isset($_GET['register'])){ ?>
<h1>🗳️ MGM'S College Of Cs & It Voting 2026 </h1>
<form method="post">
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<button name="login">Login</button>
</form>
<div class="link"><a href="?register=1">Register</a></div>

<?php } else { ?>
<h1>📝 Register</h1>
<form method="post">
<input name="name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<button name="register">Create Account</button>
</form>
<div class="back"><a href="index.php">← Back</a></div>
<?php } ?>

<?php } else { ?>

<h2>Welcome, <?=$_SESSION['user']['name']?></h2>

<?php if($_SESSION['user']['voted']==0){ ?>

<h2>🗳️ Choose your HOD </h2>
<form method="post" class="vote-container">
<?php
$c=mysqli_query($conn,"SELECT * FROM candidates");
while($row=mysqli_fetch_assoc($c)){
echo "
<label class='vote-card'>
<span>{$row['name']}</span>
<div>
<input type='radio' name='candidate' value='{$row['id']}' required>
<div class='custom-radio'></div>
</div>
</label>";
}
?>
<button name="vote">Submit Vote</button>
</form>

<?php } else { ?>

<h2>✅ Thank you for voting</h2>

<h2>📊 Live Results</h2>
<canvas id="chart"></canvas>

<script>
const ctx = document.getElementById('chart').getContext('2d');
const labels = <?= json_encode($labels) ?>;
const votes = <?= json_encode($votes) ?>;
const maxVote = Math.max(...votes);
const backgroundColors = votes.map(v => v === maxVote ? '#ff4757' : '#1dd1a1');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Votes',
            data: votes,
            backgroundColor: backgroundColors,
            borderColor: '#fff',
            borderWidth: 2,
            borderRadius: 10,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        animation: {
            duration: 1500,
            easing: 'easeOutBounce'
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                enabled: true,
                backgroundColor: '#000',
                titleColor: '#fff',
                bodyColor: '#fff'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255,255,255,0.2)' },
                ticks: { color: '#fff', stepSize: 1 }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#fff' }
            }
        }
    }
});
</script>

<?php } ?>

<div class="back"><a href="?logout=1">Logout</a></div>

<?php } ?>

</div>
</body>
</html>
