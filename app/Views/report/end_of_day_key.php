<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Management End of Day Key List</title>
    <style>
    #reportData {
        font-family: Arial, Helvetica, sans-serif;
        border-collapse: collapse;
        width: 100%;
    }

    #reportData td, #reportData th {
        border: 1px solid #ddd;
        padding: 8px;
    }

    #reportData tr:nth-child(even){background-color: #f2f2f2;}

    #reportData tr:hover {background-color: #ddd;}

    #reportData th {
        padding-top: 12px;
        padding-bottom: 12px;
        text-align: left;
        background-color: #23A7FF;
        color: white;
    }
    </style>
</head>
<body>

    <div style="margin: 0 auto;display: block;">
        <h1>Management End of Day Key Records </h1>
        <table id="reportData">
            <tr>
                <th>#</th>
                <th>Key ID</th>
                <th>Serial No</th>                
                <th>Key Type</th>                
                <th>Key Loan</th>                
                <th>Visitor Type</th>                
                <th>Name</th>                
                <th>Company</th>                
                <th>Key Out</th>                
                <th>Loan Length</th>
            </tr>
            <?php 
            $i = 1;
            foreach($data as $record){
            ?>
            <tr>
                <td><?= $i ?></td>
                <td><?= $record['key_id'] ?></td>
                <td><?= $record['serial_no'] ?></td>
                <td><?= $record['key_type'] ?></td>
                <td><?= $record['key_loan'] ?></td>
                <td><?= $record['person_type'] ?></td>
                <td><?= $record['name'] ?></td>
                <td><?= $record['company'] ?></td>
                <td><?= $record['key_out'] ?></td>
                <td><?= $record['loan_length'] ?></td>                
            </tr>
            <?php 
                $i++; 
            } 
            ?>
        </table>
    </div>
</body>
</html>