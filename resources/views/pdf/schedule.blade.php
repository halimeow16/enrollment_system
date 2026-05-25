<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 14px 18px;
        }

        body {
            margin: 0;
            color: #000;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
        }

        .header {
            border-bottom: 3px solid #808080;
            padding-bottom: 6px;
        }

        .brand-row {
            display: table;
            width: 100%;
        }

        .brand-logo,
        .brand-copy {
            display: table-cell;
            vertical-align: middle;
        }

        .brand-logo {
            width: 315px;
        }

        .brand-logo img {
            width: 305px;
            height: auto;
        }

        .brand-title {
            color: #879bd4;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 1px;
            line-height: 1;
            white-space: nowrap;
        }

        .brand-meta {
            color: #777;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            line-height: 1.3;
        }

        .schedule-title {
            margin: 20px 0 18px;
            text-align: center;
            font-family: DejaVu Serif, Times New Roman, serif;
            font-weight: 900;
        }

        .schedule-title .year {
            color: #e60000;
            font-size: 25px;
            text-decoration: underline;
        }

        .schedule-title .semester {
            color: #0b2a63;
            font-size: 23px;
        }

        .schedule-title .ay {
            color: #000;
            font-size: 23px;
        }

        .schedule-title .program {
            margin-top: 3px;
            color: #000;
            font-size: 22px;
            letter-spacing: .3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th {
            background: #000;
            border: 2px solid #000;
            color: #fff;
            font-size: 16px;
            font-weight: 800;
            padding: 8px 6px;
            text-align: center;
        }

        td {
            border: 1.5px solid #000;
            font-size: 13.5px;
            padding: 7px 7px;
            vertical-align: middle;
        }

        .code { width: 8.5%; text-align: center; }
        .subject { width: 49%; }
        .day { width: 6%; text-align: center; }
        .time { width: 18%; text-align: center; white-space: nowrap; }
        .room { width: 6.5%; text-align: center; }
        .instructor { width: 12%; }

        .empty {
            color: #555;
            font-style: italic;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand-row">
            <div class="brand-logo">
                @if($logoData)
                    <img src="{{ $logoData }}" alt="COMTEQ">
                @endif
            </div>
            <div class="brand-copy">
                <div class="brand-title">COMTEQ COMPUTER AND BUSINESS COLLEGE, INC.</div>
                <div class="brand-meta">#63 Fendler st., East Tapinac, Olongapo City, Philippines</div>
                <div class="brand-meta">Mobile no.: 09428197810 | Tel No.: (047) 602-4778 | www.comteq.edu.ph</div>
            </div>
        </div>
    </div>

    <div class="schedule-title">
        <span class="year">{{ $yearLabel }}</span>
        <span class="ay"> | </span>
        <span class="semester">{{ $semesterLabel }}</span>
        <span class="ay"> | AY {{ $academicYear }}</span>
        <div class="program">{{ $courseName }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="code">Code</th>
                <th class="subject">Subjects</th>
                <th class="day">Day</th>
                <th class="time">Time</th>
                <th class="room">Room</th>
                <th class="instructor">Instructor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedules as $schedule)
                <tr>
                    <td class="code">{{ $schedule->subject->code }}</td>
                    <td class="subject">{{ $schedule->subject->name }}</td>
                    <td class="day">{{ $dayLabel($schedule) }}</td>
                    <td class="time">{{ $timeLabel($schedule) }}</td>
                    <td class="room">{{ $schedule->room->name }}</td>
                    <td class="instructor">{{ $schedule->instructor ?: '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">No schedules found for this class.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
