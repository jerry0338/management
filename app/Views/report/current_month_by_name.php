<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Staff Current Month by Name</title>
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
        <h1>Staff Current Month by Name</h1>
        <table id="reportData">
            <tr>
                <th>#</th>
                <th>Staff Name</th>
                <th>Staff Mobile</th>                
                <th>Date</th>               
                <th>Time In</th>
                <th>Time Out</th>
                <th>Total Time</th>
            </tr>
            <?php 
            $i = 1;
            foreach($data as $record){
            ?>
            <tr>
                <td><?= $i ?></td>
                <td><?= $record['name'] ?></td>
                <td><?= $record['mobile_number'] ?></td>
                <td><?= $record['date'] ?></td>
                <td><?= $record['time_in'] ?></td>
                <td><?= $record['time_out'] ?></td>
                <td><?= $record['total_time'] ?></td>                
            </tr>
            <?php 
                $i++; 
            } 
            ?>
        </table>
    </div>
</body>
</html>