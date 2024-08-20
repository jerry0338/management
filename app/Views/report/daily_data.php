<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Management Current Visitor</title>
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
        <h1>Management Today Data </h1>
        <table id="reportData">
            <tr>
                <th>#</th>
                <th>Visitor Name</th>
                <th>Visitor Type</th>                
                <th>Company Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Time</th>
                <th>Status</th>
            </tr>
            <?php 
            $i = 1;
            foreach($data as $record){
            ?>
            <tr>
                <td><?= $i ?></td>
                <td><?= $record['name'] ?></td>
                <td><?= $record['type_name'] ?></td>
                <td><?= $record['company_name'] != '' ? $record['company_name'] : '-' ?></td>
                <td><?= $record['email'] ?></td>
                <td><?= $record['mobile_number'] ?></td>
                <td><?= $record['created_at'] ?></td>
                <td><?= $record['status'] ?></td>
                
            </tr>
            <?php 
                $i++; 
            } 
            ?>
        </table>
    </div>
</body>
</html>