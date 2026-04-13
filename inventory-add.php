<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="nav2.css">
<link rel="stylesheet" type="text/css" href="form4.css">
<link rel="stylesheet" type="text/css" href="barcode-scanner.css">
<link rel="stylesheet" type="text/css" href="ocr-scanner.css">
<title>
Medicines
</title>
</head>

<body>

	<div class="sidenav">
			<h2 style="font-family:Arial; color:white; text-align:center;"> PHARMACIA </h2>
			<a href="adminmainpage.php">Dashboard</a>
			<button class="dropdown-btn">Inventory
			<i class="down"></i>
			</button>
			<div class="dropdown-container">
				<a href="inventory-add.php">Add New Medicine</a>
				<a href="inventory-view.php">Manage Inventory</a>
			</div>
			<button class="dropdown-btn">Suppliers
			<i class="down"></i>
			</button>
			<div class="dropdown-container">
				<a href="supplier-add.php">Add New Supplier</a>
				<a href="supplier-view.php">Manage Suppliers</a>
			</div>
			<button class="dropdown-btn">Stock Purchase
			<i class="down"></i>
			</button>
			<div class="dropdown-container">
				<a href="purchase-add.php">Add New Purchase</a>
				<a href="purchase-view.php">Manage Purchases</a>
			</div>			
			<button class="dropdown-btn">Employees
			<i class="down"></i>
			</button>
			<div class="dropdown-container">
				<a href="employee-add.php">Add New Employee</a>
				<a href="employee-view.php">Manage Employees</a>
			</div>			
			<button class="dropdown-btn">Customers
			<i class="down"></i>
			</button>
			<div class="dropdown-container">
				<a href="customer-add.php">Add New Customer</a>
				<a href="customer-view.php">Manage Customers</a>
			</div>
			<a href="sales-view.php">View Sales Invoice Details</a>
			<a href="salesitems-view.php">View Sold Products Details</a>
			<a href="pos1.php">Add New Sale</a>		
			<button class="dropdown-btn">Reports
			<i class="down"></i>
			</button>
			<div class="dropdown-container">
				<a href="stockreport.php">Medicines - Low Stock</a>
				<a href="expiryreport.php">Medicines - Soon to Expire</a>
				<a href="salesreport.php">Transactions Reports</a>			
			</div>			
	</div>

	<div class="topnav">
		<a href="logout.php">Logout</a>
	</div>
	
	<center>
	<div class="head">
	<h2> ADD MEDICINE DETAILS</h2>
	</div>
	</center>
	
	
	<br><br><br><br><br><br><br><br>
	
	
	<div class="one">
		<div class="row">
			<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
				<div style="width:100%;margin-bottom:10px;">
					<button type="button" class="btn-ocr" id="inventoryOcrBtn">Capture Photo (OCR)</button>
					<span style="font-size:0.85rem;color:#555;">Auto-extract Medicine Name + Qty from package photo</span>
				</div>
				<div class="column">
					<p>
						<label for="medid">Medicine ID:</label><br>
						<input type="number" name="medid" id="medid">
					</p>
					<p>
						<label for="barcode">Barcode (optional – scan from package):</label><br>
						<input type="text" name="barcode" id="barcode" placeholder="Scan or enter barcode">
						<button type="button" class="btn-scan-barcode" onclick="openBarcodeScanner(function(code){ document.getElementById('barcode').value=code; var n=parseInt(code,10); if(!isNaN(n)&&code==''+n) document.getElementById('medid').value=code; })">Scan Barcode</button>
					</p>
					<p>
						<label for="medname">Medicine Name:</label><br>
						<input type="text" name="medname" id="medname">
					</p>
					<p>
						<label for="qty">Quantity:</label><br>
						<input type="number" name="qty" id="qty">
					</p>
					<p>
						<label for="cat">Category:</label><br>
						<select id="cat" name="cat">
								<option>Tablet</option>
								<option>Capsule</option>
								<option>Syrup</option>
						</select>
					</p>
					
				</div>
				<div class="column">
					
					<p>
						<label for="sp">Price: </label><br>
						<input type="number" step="0.01" name="sp">
					</p>
					<p>
						<label for="loc">Location:</label><br>
						<input type="text" name="loc">
					</p>
				</div>
				
			
			<input type="submit" name="add" value="Add Medicine">
			</form>
		<br>
		
		
	<?php
	
		include "config.php";
		 
		if(isset($_POST['add']))
		{
		$id = mysqli_real_escape_string($conn, $_REQUEST['medid']);
		$name = mysqli_real_escape_string($conn, $_REQUEST['medname']);
		$qty = mysqli_real_escape_string($conn, $_REQUEST['qty']);
		$category = mysqli_real_escape_string($conn, $_REQUEST['cat']);
		$sprice = mysqli_real_escape_string($conn, $_REQUEST['sp']);
		$location = mysqli_real_escape_string($conn, $_REQUEST['loc']);
		$barcode = isset($_REQUEST['barcode']) ? mysqli_real_escape_string($conn, trim($_REQUEST['barcode'])) : '';

		$dupCheck = @mysqli_query($conn, "SELECT MED_ID FROM meds WHERE MED_ID = '$id' LIMIT 1");
		if ($dupCheck && mysqli_num_rows($dupCheck) > 0) {
			$nextRes = @mysqli_query($conn, "SELECT MAX(MED_ID)+1 AS n FROM meds");
			$nextId = '';
			if ($nextRes && ($nr = mysqli_fetch_assoc($nextRes)) && isset($nr['n']) && $nr['n'] !== null) {
				$nextId = (string)$nr['n'];
			}
			echo "<p style='color:red;font-size:14px;'>Medicine ID <strong>" . htmlspecialchars($id) . "</strong> already exists. Use a new ID";
			if ($nextId !== '') {
				echo " (suggested next: <strong>" . htmlspecialchars($nextId) . "</strong>)";
			}
			echo ".</p>";
		} else {

		$hasBarcode = false;
		$checkCol = @mysqli_query($conn, "SHOW COLUMNS FROM meds LIKE 'BARCODE'");
		if ($checkCol && mysqli_num_rows($checkCol) > 0) $hasBarcode = true;

		if ($hasBarcode && $barcode !== '') {
			$sql = "INSERT INTO meds (MED_ID, MED_NAME, MED_QTY, CATEGORY, MED_PRICE, LOCATION_RACK, BARCODE) VALUES ($id, '$name', $qty,'$category',$sprice, '$location', '$barcode')";
		} else {
			$sql = "INSERT INTO meds (MED_ID, MED_NAME, MED_QTY, CATEGORY, MED_PRICE, LOCATION_RACK) VALUES ($id, '$name', $qty,'$category',$sprice, '$location')";
		}
		try {
			if (mysqli_query($conn, $sql)) {
				echo "<p style='font-size:8;'>Medicine details successfully added!</p>";
			} else {
				echo "<p style='font-size:8; color:red;'>Error! Check details.</p>";
			}
		} catch (mysqli_sql_exception $e) {
			if (strpos($e->getMessage(), 'Duplicate') !== false || $e->getCode() == 1062) {
				echo "<p style='color:red;font-size:14px;'>Medicine ID already exists. Choose a different Medicine ID.</p>";
			} else {
				echo "<p style='color:red;font-size:14px;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
			}
		}
		}
		}
		 
		$conn->close();
	?>
		</div>
	</div>
			
</body>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="js/barcode-scanner.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="js/ocr-scanner.js"></script>
<script>
		document.getElementById('inventoryOcrBtn').onclick = function () {
			openOcrScanner({
				onApply: function (s) {
					if (s.medicineName) document.getElementById('medname').value = s.medicineName;
					if (s.quantity) {
						var n = parseInt(String(s.quantity).replace(/[^0-9]/g, ''), 10);
						if (!isNaN(n)) document.getElementById('qty').value = n;
					}
				}
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


