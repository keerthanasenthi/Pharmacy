<!DOCTYPE html>
<html>

<head>
<link rel="stylesheet" type="text/css" href="nav2.css">
<link rel="stylesheet" type="text/css" href="form4.css">
<link rel="stylesheet" type="text/css" href="ocr-scanner.css">
<title>
Purchases
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
				<a href="salesreport.php">Transactions - Last Month</a>				
			</div>			
	</div>

	<div class="topnav">
		<a href="logout.php">Logout</a>
	</div>
	
	<center>
	<div class="head">
	<h2> ADD PURCHASE DETAILS</h2>
	</div>
	</center>
	
	
	<br><br><br><br><br><br><br><br>
	
	
	<div class="one row">
		<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
			<div style="width:100%;margin-bottom:10px;">
				<button type="button" class="btn-ocr" id="purchaseOcrBtn">Capture Photo (OCR)</button>
				<span style="font-size:0.85rem;color:#555;">Auto-extract Medicine Name, EXP, MFG, Qty from package photo</span>
			</div>
				
	<?php
	
		include "config.php";
		 
		if(isset($_POST['add']))
		{
		$pid = mysqli_real_escape_string($conn, $_REQUEST['pid']);
		$sid = mysqli_real_escape_string($conn, $_REQUEST['sid']);
		$mid = mysqli_real_escape_string($conn, $_REQUEST['mid']);
		$qty = mysqli_real_escape_string($conn, $_REQUEST['pqty']);
		$cost = mysqli_real_escape_string($conn, $_REQUEST['pcost']);
		$pdate = mysqli_real_escape_string($conn, $_REQUEST['pdate']);
		$mdate = mysqli_real_escape_string($conn, $_REQUEST['mdate']);
		$edate = mysqli_real_escape_string($conn, $_REQUEST['edate']);

		$sql = "INSERT INTO purchase VALUES ($pid, $sid, $mid,'$qty','$cost','$pdate','$mdate','$edate')";
		if(mysqli_query($conn, $sql)){
			echo "<p style='font-size:8;'>Purchase details successfully added!</p>";
		} else{
			echo "<p style='font-size:8;color:red;'>Error! Check details.</p>";
		}
		
		}
		 
		$conn->close();
	?>
	
			<div class="column">
					<p>
						<label for="pid">Purchase ID:</label><br>
						<input type="number" name="pid">
					</p>
					<p>
						<label for="sid">Supplier ID:</label><br>
						<input type="number" name="sid">
					</p>
					<p>
						<label for="mid">Medicine ID:</label><br>
						<input type="number" name="mid" id="mid">
					</p>
					<p>
						<label for="pqty">Purchase Quantity:</label><br>
						<input type="number" name="pqty" id="pqty">
					</p>
					
					
				</div>
				<div class="column">
					
					<p>
						<label for="pcost">Purchase Cost:</label><br>
						<input type="number" step="0.01" name="pcost">
					</p>
					
					
					<p>
						<label for="pdate">Date of Purchase:</label><br>
						<input type="date" name="pdate">
					</p>
					<p>
						<label for="mdate">Manufacturing Date:</label><br>
						<input type="date" name="mdate" id="mdate">
					</p>
					<p>
						<label for="edate">Expiry Date:</label><br>
						<input type="date" name="edate" id="edate">
					</p>
					
				</div>
				
			
			<input type="submit" name="add" value="Add Purchase">
			</form>
		<br>
	
	</div>
		
</body>

<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="js/ocr-scanner.js"></script>
<script>
		document.getElementById('purchaseOcrBtn').onclick = function () {
			openOcrScanner({
				onApply: function (s) {
					// Quantity
					if (s.quantity) {
						var n = parseInt(String(s.quantity).replace(/[^0-9]/g, ''), 10);
						if (!isNaN(n)) document.getElementById('pqty').value = n;
					}
					// Dates (best-effort parse to yyyy-mm-dd if possible)
					function trySetDate(inputId, val) {
						if (!val) return;
						val = String(val).trim();
						var el = document.getElementById(inputId);
						if (!el) return;

						// yyyy-mm-dd
						var m1 = val.match(/(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})/);
						if (m1) {
							var y = m1[1], mo = ('0' + m1[2]).slice(-2), d = ('0' + m1[3]).slice(-2);
							el.value = y + '-' + mo + '-' + d;
							return;
						}
						// dd-mm-yyyy or dd/mm/yyyy
						var m2 = val.match(/(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})/);
						if (m2) {
							var dd = ('0' + m2[1]).slice(-2), mm = ('0' + m2[2]).slice(-2), yy = m2[3].length === 2 ? ('20' + m2[3]) : m2[3];
							el.value = yy + '-' + mm + '-' + dd;
							return;
						}
						// mm/yy or mm/yyyy -> set as last day of month (approx) or first day
						var m3 = val.match(/(\d{1,2})[\/\-.](\d{2,4})/);
						if (m3) {
							var mm2 = ('0' + m3[1]).slice(-2), yy2 = m3[2].length === 2 ? ('20' + m3[2]) : m3[2];
							el.value = yy2 + '-' + mm2 + '-01';
						}
					}
					trySetDate('mdate', s.mfgDate);
					trySetDate('edate', s.expDate);

					// Medicine ID lookup by name (best-effort)
					if (s.medicineName) {
						fetch('get-medicine-by-name.php?name=' + encodeURIComponent(s.medicineName))
							.then(function (r) { return r.json(); })
							.then(function (data) {
								if (data && data.found) {
									document.getElementById('mid').value = data.med_id;
								} else {
									// leave it to manual entry
								}
							})
							.catch(function () {});
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