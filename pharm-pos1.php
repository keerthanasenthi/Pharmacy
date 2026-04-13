<!DOCTYPE html>
<html>

<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="nav2.css">
<link rel="stylesheet" type="text/css" href="form3.css">
<link rel="stylesheet" type="text/css" href="table2.css">
<link rel="stylesheet" type="text/css" href="barcode-scanner.css">
<title>
New Sales
</title>
</head>

<body>

		<div class="sidenav">
			<h2 style="font-family:Arial; color:white; text-align:center;"> PHARMACIA </h2>
			<a href="pharmmainpage.php">Dashboard</a>
			
			<a href="pharm-inventory.php">View Inventory</a>
			<a href="pharm-pos1.php">Add New Sale</a>
			<button class="dropdown-btn">Customers
			<i class="down"></i>
			</button>
			<div class="dropdown-container">
				<a href="pharm-customer.php">Add New Customer</a>
				<a href="pharm-customer-view.php">View Customers</a>
			</div>
	</div>

	<?php
		include "config.php";
		session_start();
	
		$sql="SELECT E_FNAME from EMPLOYEE WHERE E_ID='$_SESSION[user]'";
		$result=$conn->query($sql);
		$row=$result->fetch_row();
		
		$ename=$row[0];
	?>

	<div class="topnav">
		<a href="logout1.php">Logout(signed in as <?php echo $ename; ?>)</a>
	</div>
	
	<center>
	<div class="head">
	<h2> POINT OF SALE</h2>
	</div>
	</center>
	

	<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
		<center>
		
		<select id="cid" name="cid">
			<option value="0" selected="selected">*Select Customer ID (only once for a customer's sales)</option>
					
					
	<?php	
			
		$qry1="SELECT c_id FROM customer";
		$result1= $conn->query($qry1);
		echo mysqli_error($conn);
		if ($result1->num_rows > 0) {
			while($row1 = $result1->fetch_assoc()) {
				
				echo "<option>".$row1["c_id"]."</option>";
				}}
    ?>
		
    </select>
	&nbsp;&nbsp;
	<input type="submit" name="custadd" value="Add to Proceed.">
	</form>
	
    <?php
			
		$sidCurrent = '';
		if(isset($_POST['sid']) && $_POST['sid'] !== '') {
			$sidCurrent = $_POST['sid'];
		} elseif(isset($_GET['sid'])) {
			$sidCurrent = $_GET['sid'];
		}
		
		if(isset($_POST['cid']))
			$cid=$_POST['cid'];
		
		if(isset($_POST['custadd'])) {
			
				$qry2="INSERT INTO sales(c_id,e_id) VALUES ('$cid','$_SESSION[user]')";
				if(!($result2=$conn->query($qry2))) {
					echo "<p style='font-size:8; color:red;'>Invalid! Enter valid Customer ID to record Sales.</p>";	
					echo "<p style='font-size:8; color:red;'>" . htmlspecialchars(mysqli_error($conn)) . "</p>";
				} else {
					// Keep created sale id for the next step (Search/Add Medicine)
					$sidCurrent = (string)mysqli_insert_id($conn);
		}}
			
			
	?>
			
		<form method="post" id="searchMedForm">
		<?php if(!empty($sidCurrent)) { ?>
			<input type="hidden" name="sid" value="<?php echo htmlspecialchars($sidCurrent); ?>">
		<?php } ?>
		<select id="med" name="med">
			<option value="0" selected="selected">Select Medicine</option>
					
					
	<?php	
		$qry3="SELECT med_name FROM meds";
		$result3 = $conn->query($qry3);
		echo mysqli_error($conn);
		if ($result3->num_rows > 0) {
			while($rowOpt = $result3->fetch_assoc()) {
				
				echo "<option>".$rowOpt["med_name"]."</option>";
		}}
    ?>
		
    </select>
	&nbsp;&nbsp;
	<input type="submit" name="search" value="Search">
	&nbsp;&nbsp;
	<button type="button" class="btn-scan-barcode" id="posScanBtn">Scan Barcode</button>
	</form>
	
	<br><br><br>
	</center>
	
	
	<?php
	
		$row4 = null;
		if(isset($_POST['search'])&&! empty($_POST['med'])) {
	
			$med=$_POST['med'];
			
			$qry4="SELECT * FROM meds where med_name='$med'";
			$result4=$conn->query($qry4); 
			if ($result4) $row4 = $result4 -> fetch_row();
					
		}
	?>
	<?php
		// When user clicks "Add Medicine", insert sale_items even if $row4 is empty
		// (because $row4 is only populated during "search").
		if(isset($_POST['add'])) {
			$sid = isset($_POST['sid']) && $_POST['sid'] !== '' ? $_POST['sid'] : '';
			if($sid === '') {
				$qry5="select sale_id from sales ORDER BY sale_id DESC LIMIT 1";
				$result5=$conn->query($qry5);
				$row5=$result5->fetch_row();
				$sid=$row5[0];
			}

			$mid = isset($_POST['medid']) ? $_POST['medid'] : '';
			$aqty = isset($_POST['mqty']) ? $_POST['mqty'] : 0;
			$qty = isset($_POST['mcqty']) ? $_POST['mcqty'] : '';

			$qtyStr = isset($_POST['mcqty']) ? (string)$_POST['mcqty'] : '';
			$aqtyStr = isset($_POST['mqty']) ? (string)$_POST['mqty'] : '';

			if ($mid === '' || $mid === '0') {
				echo "<p style='font-size:8; color:red;'>Failed to add sale item: Medicine ID missing.</p>";
			} elseif($qtyStr === '') {
				echo "<p style='font-size:8; color:red;'>QUANTITY INVALID! Quantity required is empty.</p>";
			} elseif((int)$qty > (int)$aqty || (int)$qty === 0) {
				echo "<p style='font-size:8; color:red;'>QUANTITY INVALID! Requested: " . htmlspecialchars($qtyStr) . " (available: " . htmlspecialchars($aqtyStr) . ").</p>";
			} else {
				$mprice = isset($_POST['mprice']) ? $_POST['mprice'] : 0;
				$price = ((float)$mprice) * ((int)$qty);
				$qry6="INSERT INTO sales_items(`sale_id`,`med_id`,`sale_qty`,`tot_price`) VALUES($sid,$mid,$qty,$price)";
				$result6 = mysqli_query($conn,$qry6);
				if(!$result6) {
					echo "<p style='font-size:8; color:red;'>Failed to add sale item. " . htmlspecialchars(mysqli_error($conn)) . "</p>";
				} else {
					echo "<br><br> <center>";
					echo "<a class='button1 view-btn' href=pharm-pos2.php?sid=".$sid.">View Order</a>";
					echo "</center>";
				}
			}
		}
	?>
			<?php if (!empty($row4)) { ?>
			<div class="one row" style="margin-right:160px;">
			<form method="post">
					<?php if(!empty($sidCurrent)) { ?>
						<input type="hidden" name="sid" value="<?php echo htmlspecialchars($sidCurrent); ?>">
					<?php } ?>
					<div class="column">
					
					<label for="medid">Medicine ID:</label>
					<input type="number" name="medid" value="<?php echo htmlspecialchars($row4[0]); ?>" readonly><br><br>
					
					<label for="mdname">Medicine Name:</label>
					<input type="text" name="mdname" value="<?php echo htmlspecialchars($row4[1]); ?>" readonly><br><br>
					
					</div>
					<div class="column">
					
					<label for="mcat">Category:</label>
					<input type="text" name="mcat" value="<?php echo htmlspecialchars($row4[3]); ?>" readonly><br><br>
					
					<label for="mloc">Location:</label>
					<input type="text" name="mloc" value="<?php echo htmlspecialchars($row4[5]); ?>" readonly><br><br>
					
					</div>
					<div class="column">
					
					<label for="mqty">Quantity Available:</label>
					<input type="number" name="mqty" value="<?php echo htmlspecialchars($row4[2]); ?>" readonly><br><br>
					
					<label for="mprice">Price of One Unit:</label>
					<input type="number" name="mprice" value="<?php echo htmlspecialchars($row4[4]); ?>" readonly><br><br>
					
					</div>
					<label for="mcqty">Quantity Required:</label>
					<input type="number" name="mcqty">
					&nbsp;&nbsp;&nbsp;
					<input type="submit" name="add" value="Add Medicine">&nbsp;&nbsp;&nbsp;
			
		
		</form>
	</div>
			<?php } ?>
	
</body>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="js/barcode-scanner.js"></script>
<script>
		document.getElementById('posScanBtn').onclick = function() {
			openBarcodeScanner(function(code) {
				fetch('get-medicine-by-barcode.php?code=' + encodeURIComponent(code))
					.then(function(r) { return r.json(); })
					.then(function(data) {
						if (data.found) {
							var sel = document.getElementById('med');
							sel.value = data.med_name;
							document.getElementById('searchMedForm').submit();
						} else {
							alert('Medicine not found for barcode: ' + code + '. Add it in Inventory first or use Medicine ID as barcode.');
						}
					})
					.catch(function() { alert('Lookup failed. Try again.'); });
			});
		};

		var dropdown = document.getElementsByClassName("dropdown-btn");
		var i;

			for (i = 0; i < dropdown.length; i++) {
			  dropdown[i].addEventListener("click", function() {
			  this.classList.toggle("active");
			  var dropdownContent = this.nextElementSibling;
			  if (dropdownContent.style.display === "block") {
			  dropdownContent.style.display = "none";
			  } else {
			  dropdownContent.style.display = "block";
			  }
			  });
			}		
</script>

</html>