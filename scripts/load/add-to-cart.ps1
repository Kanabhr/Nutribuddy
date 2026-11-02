param(
    [string]$BaseUrl = "http://127.0.0.1:8000",
    [Parameter(Mandatory = $true)]
    [string]$Cookie,
    [Parameter(Mandatory = $true)]
    [string]$CsrfToken,
    [Parameter(Mandatory = $true)]
    [int]$ProductId,
    [int]$Quantity = 1,
    [int]$Users = 100
)

$endpoint = "$($BaseUrl.TrimEnd('/'))/user/cart"
$body = @{
    product_id = $ProductId
    quantity = $Quantity
} | ConvertTo-Json

$jobs = 1..$Users | ForEach-Object {
    Start-Job -ScriptBlock {
        param($endpoint, $body, $cookie, $csrfToken)

        $stopwatch = [System.Diagnostics.Stopwatch]::StartNew()
        try {
            $response = Invoke-WebRequest `
                -Uri $endpoint `
                -Method Post `
                -Headers @{
                    "Accept" = "application/json"
                    "Content-Type" = "application/json"
                    "X-CSRF-TOKEN" = $csrfToken
                    "Cookie" = $cookie
                } `
                -Body $body `
                -UseBasicParsing

            $statusCode = [int]$response.StatusCode
        } catch {
            $statusCode = if ($_.Exception.Response) {
                [int]$_.Exception.Response.StatusCode
            } else {
                0
            }
        } finally {
            $stopwatch.Stop()
        }

        [pscustomobject]@{
            StatusCode = $statusCode
            Ms = [math]::Round($stopwatch.Elapsed.TotalMilliseconds, 2)
        }
    } -ArgumentList $endpoint, $body, $Cookie, $CsrfToken
}

$results = $jobs | Receive-Job -Wait -AutoRemoveJob
$slow = $results | Where-Object { $_.Ms -gt 200 }

$results |
    Group-Object StatusCode |
    Sort-Object Name |
    Select-Object @{Name = "Status"; Expression = { $_.Name }}, Count |
    Format-Table -AutoSize

[pscustomobject]@{
    Endpoint = $endpoint
    Requests = $results.Count
    AverageMs = [math]::Round(($results | Measure-Object Ms -Average).Average, 2)
    P95Ms = [math]::Round((($results | Sort-Object Ms)[[math]::Floor($results.Count * 0.95) - 1]).Ms, 2)
    MaxMs = [math]::Round(($results | Measure-Object Ms -Maximum).Maximum, 2)
    SlowOver200Ms = $slow.Count
} | Format-List

if ($slow.Count -gt 0) {
    Write-Warning "$($slow.Count) requests exceeded 200ms."
}
