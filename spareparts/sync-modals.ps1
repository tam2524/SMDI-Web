$ho_path = "c:\xampp\htdocs\SMDI-Web\spareparts\headoffice_spareparts.php"
$branch_path = "c:\xampp\htdocs\SMDI-Web\spareparts\branch_spareparts.php"

$ho_content = Get-Content -Path $ho_path -Raw
# Split into lines manually or read as array
$ho_lines = Get-Content -Path $ho_path

# Array indexes are 0-based.
# 843 to 1666 -> index 842 to 1665
$part1 = $ho_lines[842..1665] -join "`r`n"
# 1683 to 2185 -> index 1682 to 2184
$part2 = $ho_lines[1682..2184] -join "`r`n"

$new_modals = $part1 + "`r`n`r`n" + $part2

$branch_lines = Get-Content -Path $branch_path
# Branch modals start at 763 (index 762), end at 1762 (index 1761).
$branch_start = $branch_lines[0..761] -join "`r`n"
$branch_end = $branch_lines[1762..($branch_lines.Length - 1)] -join "`r`n"

$new_branch_content = $branch_start + "`r`n" + $new_modals + "`r`n" + $branch_end

Set-Content -Path $branch_path -Value $new_branch_content

Write-Output "Modals synced successfully!"
