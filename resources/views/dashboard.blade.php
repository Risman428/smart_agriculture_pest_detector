<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>🌱 Smart Agriculture Dashboard</title>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fredoka+One&display=swap');

        html, body {
            height: 100%;
        }

        body {
            min-height: 100vh;
            font-family: 'Fredoka One', cursive;
            background: linear-gradient(to bottom, #e0f7fa, #a7ffeb);
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #1b5e20;
            text-align: center;
            padding: 20px;
            margin: 0;
            padding-bottom: 60px;
        }

        h1 {
            margin-bottom: 25px;
            color: #2e7d32;
            text-shadow: 1px 1px 2px #fff;
        }

        /* ===== STATUS BOX ===== */
        .status-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 35px;
            flex-wrap: wrap;
        }

        .status-box {
            background: #c8e6c9;
            padding: 18px 15px;
            border-radius: 16px;
            width: 130px;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.2);
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.3s, background 0.3s;
            cursor: default;
        }

        .status-box.on {
            background: #81c784;
        }

        .status-box.off {
            background: #aed581;
        }

        .status-box:hover {
            transform: scale(1.05);
            box-shadow: 4px 4px 15px rgba(0,0,0,0.3);
            background: #ffeb3b;
        }

        .label {
            font-size: 14px;
            margin-bottom: 8px;
            color: #33691e;
        }

        .value {
            font-size: 20px;
        }

        /* ===== TABLE ===== */
        table {
            margin: 0 auto 40px auto;
            border-collapse: collapse;
            width: 60%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 2px 2px 10px rgba(0,0,0,0.15);
            border-radius: 12px;
            overflow: hidden;
            font-size: 16px;
        }

        th, td {
            padding: 10px 15px;
            border-bottom: 1px solid #dcedc8;
        }

        th {
            background: #388e3c;
            color: #fffde7;
        }

        td {
            color: #33691e;
        }

        #history-table tr {
            transition: background 0.3s;
        }

        #history-table tr:hover {
            background: #c5e1a5;
        }

        @media(max-width: 600px) {
            .status-container {
                flex-direction: column;
                gap: 15px;
            }

            table {
                width: 90%;
            }
        }
    </style>
</head>

<body>

    <h1>🌱 Smart Agriculture Dashboard</h1>

    <!-- ===== STATUS BOX ===== -->
    <div class="status-container">

        <div class="status-box">
            <div class="label">Suhu</div>
            <div class="value" id="temp">-- °C</div>
        </div>

        <div class="status-box">
            <div class="label">Kelembapan</div>
            <div class="value" id="hum">-- %</div>
        </div>

        <div class="status-box">
            <div class="label">Jarak</div>
            <div class="value" id="distance">-- cm</div>
        </div>

        <div class="status-box off" id="servo-box">
            <div class="label">Servo</div>
            <div class="value" id="servo">OFF</div>
        </div>

        <div class="status-box off" id="buzzer-box">
            <div class="label">Buzzer</div>
            <div class="value" id="buzzer">OFF</div>
        </div>

    </div>

    <!-- ===== TABLE HISTORY ===== -->
    <table id="history-table">
        <tr>
            <th>No</th>
            <th>Waktu Hama Terdeteksi</th>
        </tr>
    </table>

    <!-- ===== SCRIPT ===== -->
    <script>
        const months = [
            "Januari","Februari","Maret","April","Mei","Juni",
            "Juli","Agustus","September","Oktober","November","Desember"
        ];

        function formatDate(date) {
            let day = date.getDate();
            let month = months[date.getMonth()];
            let year = date.getFullYear();

            let hours = String(date.getHours()).padStart(2, '0');
            let minutes = String(date.getMinutes()).padStart(2, '0');
            let seconds = String(date.getSeconds()).padStart(2, '0');

            return `${day} ${month} ${year}, ${hours}:${minutes}:${seconds}`;
        }

        function updateDashboard() {
            $.getJSON('/api/latest', function(data) {

                $('#temp').text(data.temperature + ' °C');
                $('#hum').text(data.humidity + ' %');
                $('#distance').text(data.distance + ' cm');

                // Servo
                $('#servo').text(data.servo ? 'ON' : 'OFF');
                $('#servo-box').attr(
                    'class',
                    'status-box ' + (data.servo ? 'on' : 'off')
                );

                // Buzzer
                $('#buzzer').text(data.buzzer ? 'ON' : 'OFF');
                $('#buzzer-box').attr(
                    'class',
                    'status-box ' + (data.buzzer ? 'on' : 'off')
                );

                // History
                let table = $('#history-table');
                table.find("tr:gt(0)").remove();

                data.history.forEach((row, index) => {
                    let date = new Date(row.created_at);
                    table.append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>${formatDate(date)}</td>
                        </tr>
                    `);
                });
            });
        }

        setInterval(updateDashboard, 1000);
        updateDashboard();
    </script>

</body>
</html>
