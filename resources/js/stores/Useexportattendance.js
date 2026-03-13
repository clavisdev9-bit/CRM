/**
 * useExportAttendance.js
 * Composable untuk export absensi ke Excel, PDF, dan CSV
 *
 * Install dependencies:
 *   npm install xlsx jspdf jspdf-autotable
 */

import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

// =============================================
// HELPER — nama bulan
// =============================================
const bulanList = [
    'Januari','Februari','Maret','April','Mei','Juni',
    'Juli','Agustus','September','Oktober','November','Desember'
]

function getBulanLabel(month, year) {
    return `${bulanList[month - 1]} ${year}`
}

function formatDate(dateStr) {
    if (!dateStr) return '-'
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return '-'
    return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: '2-digit' })
}


// =====================================================================
// EXPORT LAPORAN BULANAN (AbsensiSales)
// =====================================================================

/**
 * Export laporan bulanan ke Excel (.xlsx)
 * @param {Object} reportData   - data dari store.reportData
 * @param {Array}  attendanceDays - data dari store.attendanceDays
 * @param {Object} reportSummary  - data dari store.reportSummary
 */
export function exportReportExcel(reportData, attendanceDays, reportSummary) {
    const user   = reportData?.user
    const period = reportData?.period

    const wb = XLSX.utils.book_new()

    // ===== SHEET 1: Rekap Per Hari =====
    const headerRow1 = ['No', 'Nama', 'Username']
    const headerRow2 = ['', '', '']

    attendanceDays.forEach(d => {
        headerRow1.push(String(d.day))
        headerRow2.push(d.day_name)
    })

    headerRow1.push(...['H', 'T', 'L', 'A', 'Total Hadir'])
    headerRow2.push(...['Hadir', 'Terlambat', 'Libur', 'Absen', ''])

    // Baris data user
    const dataRow = [
        1,
        user?.fullname ?? '-',
        user?.username ?? '-',
    ]

    let absenCount = 0
    attendanceDays.forEach(d => {
        if (d.is_weekend)          dataRow.push('L')
        else if (!d.check_in)    { dataRow.push(''); absenCount++ }
        else if (d.status === 'LATE') dataRow.push('T')
        else                          dataRow.push('H')
    })

    dataRow.push(
        reportSummary.ONTIME,
        reportSummary.LATE,
        reportSummary.LIBUR,
        absenCount,
        reportSummary.TOTAL_HADIR,
    )

    const wsData = [
        [`LAPORAN ABSENSI — ${getBulanLabel(period?.month, period?.year)}`],
        [`Nama: ${user?.fullname}  |  Username: ${user?.username}  |  Email: ${user?.email}`],
        [],
        headerRow1,
        headerRow2,
        dataRow,
        [],
        ['Keterangan:', '', '', 'H = Hadir (Ontime)', 'T = Terlambat', 'L = Libur/Weekend', 'A = Absen'],
    ]

    const ws = XLSX.utils.aoa_to_sheet(wsData)
    ws['!cols'] = [
        { wch: 5 }, { wch: 25 }, { wch: 15 },
        ...Array(attendanceDays.length).fill({ wch: 4 }),
        ...Array(5).fill({ wch: 10 }),
    ]
    XLSX.utils.book_append_sheet(wb, ws, 'Rekap Bulanan')

    // ===== SHEET 2: Detail Per Hari =====
    const detailRows = [
        ['Tanggal', 'Hari', 'Status', 'Check In', 'Check Out', 'Lokasi IN', 'Mode', 'Device']
    ]

    attendanceDays.forEach(d => {
        detailRows.push([
            d.date,
            d.day_name,
            d.is_weekend ? 'Libur' : (d.status ?? '-'),
            d.check_in?.time  ?? '-',
            d.check_out?.time ?? '-',
            d.check_in?.location_name ?? '-',
            d.check_in?.attendance_mode ?? '-',
            d.check_in?.device_type ?? '-',
        ])
    })

    const wsDetail = XLSX.utils.aoa_to_sheet(detailRows)
    wsDetail['!cols'] = [
        { wch: 14 }, { wch: 6 }, { wch: 12 }, { wch: 10 },
        { wch: 10 }, { wch: 50 }, { wch: 8 }, { wch: 10 },
    ]
    XLSX.utils.book_append_sheet(wb, wsDetail, 'Detail Harian')

    XLSX.writeFile(wb, `Absensi_${user?.username}_${getBulanLabel(period?.month, period?.year)}.xlsx`)
}


/**
 * Export laporan bulanan ke PDF
 */
export function exportReportPdf(reportData, attendanceDays, reportSummary) {
    const user   = reportData?.user
    const period = reportData?.period

    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })

    // Header
    doc.setFontSize(14)
    doc.setFont('helvetica', 'bold')
    doc.text(`LAPORAN ABSENSI — ${getBulanLabel(period?.month, period?.year)}`, 14, 15)

    doc.setFontSize(10)
    doc.setFont('helvetica', 'normal')
    doc.text(`Nama: ${user?.fullname}   Username: ${user?.username}   Email: ${user?.email}`, 14, 22)

    // Summary box
    doc.setFontSize(9)
    const summaryItems = [
        `Total Hadir : ${reportSummary.TOTAL_HADIR}`,
        `Ontime      : ${reportSummary.ONTIME}`,
        `Terlambat   : ${reportSummary.LATE}`,
        `Libur       : ${reportSummary.LIBUR}`,
        `Checkout    : ${reportSummary.TOTAL_CHECKOUT}`,
    ]
    summaryItems.forEach((item, i) => {
        doc.text(item, 14 + (i * 52), 29)
    })

    // Tabel detail harian
    const columns = ['Tgl', 'Hari', 'Status', 'Check In', 'Check Out', 'Lokasi', 'Mode', 'Device']
    const rows = attendanceDays.map(d => [
        d.day,
        d.day_name,
        d.is_weekend ? 'Libur' : (d.status ?? '-'),
        d.check_in?.time  ?? '-',
        d.check_out?.time ?? '-',
        d.check_in?.location_name
            ? d.check_in.location_name.substring(0, 40) + '...'
            : '-',
        d.check_in?.attendance_mode ?? '-',
        d.check_in?.device_type ?? '-',
    ])

    autoTable(doc, {
        startY: 34,
        head: [columns],
        body: rows,
        styles: { fontSize: 7, cellPadding: 1.5 },
        headStyles: { fillColor: [67, 97, 238], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [245, 247, 255] },
        columnStyles: {
            0: { cellWidth: 8 },
            1: { cellWidth: 10 },
            2: { cellWidth: 20 },
            3: { cellWidth: 18 },
            4: { cellWidth: 18 },
            5: { cellWidth: 80 },
            6: { cellWidth: 15 },
            7: { cellWidth: 15 },
        },
        didDrawCell: (data) => {
            // Warna baris weekend abu-abu
            if (data.section === 'body') {
                const row = attendanceDays[data.row.index]
                if (row?.is_weekend) {
                    doc.setFillColor(220, 220, 220)
                    doc.rect(data.cell.x, data.cell.y, data.cell.width, data.cell.height, 'F')
                    doc.setTextColor(100)
                    doc.setFontSize(7)
                    doc.text(String(data.cell.raw ?? ''), data.cell.x + 1.5, data.cell.y + 3.5)
                }
            }
        }
    })

    // Footer
    const pageCount = doc.internal.getNumberOfPages()
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i)
        doc.setFontSize(8)
        doc.setTextColor(150)
        doc.text(
            `Dicetak: ${new Date().toLocaleDateString('id-ID')}   |   Halaman ${i} dari ${pageCount}`,
            14,
            doc.internal.pageSize.height - 8
        )
    }

    doc.save(`Absensi_${user?.username}_${getBulanLabel(period?.month, period?.year)}.pdf`)
}


// =====================================================================
// EXPORT RIWAYAT ABSENSI (AbsensiHistory)
// =====================================================================

/**
 * Export riwayat ke Excel (.xlsx)
 * @param {Array} historyData - data dari store.historyData
 */
export function exportHistoryExcel(historyData) {
    const wb = XLSX.utils.book_new()

    const rows = [
        ['No', 'Tanggal', 'Waktu', 'Tipe', 'Status', 'Mode', 'Lokasi', 'Device', 'IP Address', 'Catatan']
    ]

    historyData.forEach((item, idx) => {
        rows.push([
            idx + 1,
            item.attendance_date,
            item.attendance_time,
            item.attendance_type,
            item.attendance_status,
            item.attendance_mode,
            item.location_name ?? '-',
            item.device_type,
            item.ip_address,
            item.noted ?? '-',
        ])
    })

    const ws = XLSX.utils.aoa_to_sheet(rows)
    ws['!cols'] = [
        { wch: 5 }, { wch: 14 }, { wch: 10 }, { wch: 8 }, { wch: 12 },
        { wch: 8 }, { wch: 60 }, { wch: 10 }, { wch: 16 }, { wch: 20 },
    ]

    XLSX.utils.book_append_sheet(wb, ws, 'Riwayat Absensi')
    XLSX.writeFile(wb, `Riwayat_Absensi_${new Date().toISOString().slice(0, 10)}.xlsx`)
}


/**
 * Export riwayat ke PDF
 */
export function exportHistoryPdf(historyData, userName = '') {
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })

    doc.setFontSize(13)
    doc.setFont('helvetica', 'bold')
    doc.text('RIWAYAT ABSENSI', 14, 14)

    doc.setFontSize(9)
    doc.setFont('helvetica', 'normal')
    doc.text(`User: ${userName}   |   Total: ${historyData.length} data   |   Dicetak: ${new Date().toLocaleDateString('id-ID')}`, 14, 21)

    const columns = ['No', 'Tanggal', 'Waktu', 'Tipe', 'Status', 'Mode', 'Lokasi', 'Device']
    const rows = historyData.map((item, idx) => [
        idx + 1,
        item.attendance_date,
        item.attendance_time,
        item.attendance_type,
        item.attendance_status,
        item.attendance_mode,
        item.location_name
            ? item.location_name.substring(0, 45) + '...'
            : '-',
        item.device_type,
    ])

    autoTable(doc, {
        startY: 26,
        head: [columns],
        body: rows,
        styles: { fontSize: 8, cellPadding: 2 },
        headStyles: { fillColor: [67, 97, 238], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [245, 247, 255] },
        columnStyles: {
            0: { cellWidth: 8 },
            1: { cellWidth: 24 },
            2: { cellWidth: 18 },
            3: { cellWidth: 14 },
            4: { cellWidth: 22 },
            5: { cellWidth: 14 },
            6: { cellWidth: 90 },
            7: { cellWidth: 18 },
        },
        didDrawCell: (data) => {
            if (data.section === 'body') {
                const item = historyData[data.row.index]
                if (item?.attendance_type === 'IN') {
                    // tipe IN sedikit biru muda
                } else if (item?.attendance_type === 'OUT') {
                    // tipe OUT sedikit merah muda
                }
            }
        }
    })

    const pageCount = doc.internal.getNumberOfPages()
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i)
        doc.setFontSize(8)
        doc.setTextColor(150)
        doc.text(
            `Halaman ${i} dari ${pageCount}`,
            doc.internal.pageSize.width - 30,
            doc.internal.pageSize.height - 8
        )
    }

    doc.save(`Riwayat_Absensi_${new Date().toISOString().slice(0, 10)}.pdf`)
}


/**
 * Export riwayat ke CSV
 */
export function exportHistoryCsv(historyData) {
    const headers = ['No','Tanggal','Waktu','Tipe','Status','Mode','Lokasi','Device','IP','Catatan']

    const rows = historyData.map((item, idx) => [
        idx + 1,
        item.attendance_date,
        item.attendance_time,
        item.attendance_type,
        item.attendance_status,
        item.attendance_mode,
        `"${(item.location_name ?? '-').replace(/"/g, '""')}"`,
        item.device_type,
        item.ip_address,
        `"${(item.noted ?? '-').replace(/"/g, '""')}"`,
    ])

    const csvContent = [headers, ...rows]
        .map(row => row.join(','))
        .join('\n')

    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' })
    const url  = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href     = url
    link.download = `Riwayat_Absensi_${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(url)
}
