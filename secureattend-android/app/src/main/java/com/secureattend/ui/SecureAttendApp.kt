package com.secureattend.ui

import android.graphics.Bitmap
import android.os.SystemClock
import android.util.Base64
import android.content.pm.PackageManager
import android.Manifest.permission.ACCESS_COARSE_LOCATION
import android.Manifest.permission.ACCESS_FINE_LOCATION
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import androidx.core.location.LocationCompat
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import com.google.android.gms.tasks.CancellationTokenSource
import com.secureattend.data.AttendanceApiFactory
import com.secureattend.data.AttendanceRequest
import com.secureattend.data.DashboardResponse
import com.secureattend.data.LoginRequest
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.launch
import kotlinx.coroutines.tasks.await
import retrofit2.HttpException
import java.io.ByteArrayOutputStream
import java.io.IOException

@Composable
fun SecureAttendApp() {
    var token by rememberSaveable { mutableStateOf("") }
    var userRole by rememberSaveable { mutableStateOf("") }
    var error by rememberSaveable { mutableStateOf("") }
    var email by rememberSaveable { mutableStateOf("") }
    var password by rememberSaveable { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    if (token.isBlank()) {
        LoginScreen(
            email = email,
            password = password,
            errorMessage = error,
            isLoading = isLoading,
            onEmailChange = { email = it },
            onPasswordChange = { password = it },
            onLoginClick = {
                scope.launch {
                    isLoading = true
                    error = ""
                    try {
                        val response = AttendanceApiFactory.create().login(
                            LoginRequest(
                                email = email.trim().takeIf { it.contains("@") },
                                username = email.trim().takeUnless { it.contains("@") },
                                password = password
                            )
                        )
                        token = response.token
                        userRole = response.user.role
                    } catch (e: Exception) {
                        if (e is CancellationException) throw e
                        error = e.toUserMessage("Login gagal")
                    } finally {
                        isLoading = false
                    }
                }
            }
        )
    } else {
        DashboardScreen(
            token = token,
            role = userRole,
            onLogout = { token = ""; userRole = ""; error = "" }
        )
    }
}

@Composable
fun LoginScreen(
    email: String,
    password: String,
    errorMessage: String,
    isLoading: Boolean,
    onEmailChange: (String) -> Unit,
    onPasswordChange: (String) -> Unit,
    onLoginClick: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Color(0xFFF5F7FF))
            .padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Card(
            modifier = Modifier.fillMaxWidth(),
            elevation = CardDefaults.cardElevation(defaultElevation = 8.dp)
        ) {
            Column(
                modifier = Modifier.padding(24.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                Text(
                    text = "SecureAttend",
                    style = MaterialTheme.typography.headlineMedium,
                    fontWeight = FontWeight.Bold
                )
                Text(
                    text = "Smart attendance with location and selfie proof",
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                OutlinedTextField(
                    value = email,
                    onValueChange = onEmailChange,
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Email / Username") }
                )

                OutlinedTextField(
                    value = password,
                    onValueChange = onPasswordChange,
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Password") },
                    singleLine = true
                )

                if (errorMessage.isNotBlank()) {
                    Text(
                        text = errorMessage,
                        color = MaterialTheme.colorScheme.error
                    )
                }

                Button(
                    onClick = onLoginClick,
                    enabled = !isLoading,
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(if (isLoading) "Masuk..." else "Masuk")
                }
            }
        }
    }
}

@Composable
fun DashboardScreen(token: String, role: String, onLogout: () -> Unit) {
    val context = LocalContext.current
    var dashboard by remember { mutableStateOf<DashboardResponse?>(null) }
    var error by remember { mutableStateOf("") }
    var attendanceError by remember { mutableStateOf("") }
    var attendanceMessage by remember { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(true) }
    var refreshKey by remember { mutableStateOf(0) }
    var isLoggingOut by remember { mutableStateOf(false) }
    var isSubmittingAttendance by remember { mutableStateOf(false) }
    var pendingCheckOut by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()
    val api = remember { AttendanceApiFactory.create() }
    val locationClient = remember(context) { LocationServices.getFusedLocationProviderClient(context) }

    val cameraLauncher = rememberLauncherForActivityResult(ActivityResultContracts.TakePicturePreview()) { bitmap ->
        if (bitmap == null) {
            attendanceError = "Foto selfie dibatalkan. Absensi belum dikirim."
        } else {
            scope.launch {
                isSubmittingAttendance = true
                attendanceError = ""
                attendanceMessage = ""
                try {
                    val location = locationClient.getCurrentLocation(
                        Priority.PRIORITY_HIGH_ACCURACY,
                        CancellationTokenSource().token
                    ).await() ?: throw IllegalStateException("Lokasi GPS belum tersedia. Coba lagi di area terbuka.")

                    val photo = ByteArrayOutputStream().use { output ->
                        check(bitmap.compress(Bitmap.CompressFormat.JPEG, 75, output)) {
                            "Foto selfie gagal diproses."
                        }
                        Base64.encodeToString(output.toByteArray(), Base64.NO_WRAP)
                    }
                    val ageMs = ((SystemClock.elapsedRealtimeNanos() - location.elapsedRealtimeNanos) / 1_000_000L)
                        .coerceAtLeast(0L)
                    val payload = AttendanceRequest(
                        latitude = location.latitude,
                        longitude = location.longitude,
                        accuracy = location.accuracy,
                        photo = photo,
                        mock_location_detected = LocationCompat.isMock(location),
                        location_age_ms = ageMs
                    )
                    val response = if (pendingCheckOut) {
                        api.checkOut("Bearer $token", payload)
                    } else {
                        api.checkIn("Bearer $token", payload)
                    }
                    attendanceMessage = response.message
                    refreshKey++
                } catch (e: Exception) {
                    if (e is CancellationException) throw e
                    attendanceError = e.toUserMessage("Absensi gagal")
                } finally {
                    isSubmittingAttendance = false
                }
            }
        }
    }
    val locationPermissionLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { permissions ->
        if (permissions[ACCESS_FINE_LOCATION] == true) {
            cameraLauncher.launch(null)
        } else {
            attendanceError = "Izin lokasi presisi diperlukan untuk melakukan absensi."
        }
    }

    fun startAttendance(checkOut: Boolean) {
        pendingCheckOut = checkOut
        attendanceError = ""
        attendanceMessage = ""
        val fineGranted = ContextCompat.checkSelfPermission(context, ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED
        val coarseGranted = ContextCompat.checkSelfPermission(context, ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED
        if (fineGranted || coarseGranted) {
            if (fineGranted) {
                cameraLauncher.launch(null)
            } else {
                attendanceError = "Izin lokasi presisi diperlukan; lokasi perkiraan tidak cukup akurat."
            }
        } else {
            locationPermissionLauncher.launch(arrayOf(ACCESS_FINE_LOCATION, ACCESS_COARSE_LOCATION))
        }
    }

    LaunchedEffect(token, refreshKey) {
        isLoading = true
        error = ""
        try {
            dashboard = api.dashboard("Bearer $token")
        } catch (e: Exception) {
            if (e is CancellationException) throw e
            error = e.toUserMessage("Gagal memuat dashboard")
        } finally {
            isLoading = false
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Color(0xFFF5F7FF))
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column {
                Text("Dashboard", style = MaterialTheme.typography.headlineMedium)
                Text("Selamat datang di SecureAttend", color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            OutlinedButton(
                enabled = !isLoggingOut,
                onClick = {
                    scope.launch {
                        isLoggingOut = true
                        try {
                            api.logout("Bearer $token")
                            onLogout()
                        } catch (e: Exception) {
                            if (e is CancellationException) throw e
                            error = e.toUserMessage("Logout gagal")
                        } finally {
                            isLoggingOut = false
                        }
                    }
                }
            ) {
                Text(if (isLoggingOut) "Keluar..." else "Keluar")
            }
        }

        if (error.isNotBlank()) {
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer)
            ) {
                Column(
                    modifier = Modifier.padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Text(error, color = MaterialTheme.colorScheme.onErrorContainer)
                    OutlinedButton(onClick = { refreshKey++ }) {
                        Text("Coba lagi")
                    }
                }
            }
        }

        if (isLoading && dashboard == null) {
            CircularProgressIndicator(modifier = Modifier.align(Alignment.CenterHorizontally))
        }

        dashboard?.let { data ->
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                StatCard(title = "Karyawan", value = data.stats["employees"] ?: 0, modifier = Modifier.weight(1f))
                StatCard(title = "Hadir", value = data.stats["present"] ?: 0, modifier = Modifier.weight(1f))
                StatCard(title = "Perlu review", value = data.stats["late"] ?: 0, modifier = Modifier.weight(1f))
            }

            Card(
                modifier = Modifier.fillMaxWidth(),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
            ) {
                Column(
                    modifier = Modifier.padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Text("Kehadiran terbaru", fontWeight = FontWeight.SemiBold)
                    if (data.recent.isEmpty()) {
                        Text("Belum ada data absensi.")
                    } else {
                        data.recent.forEach { item ->
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Column(modifier = Modifier.weight(1f)) {
                                    Text(item.name, fontWeight = FontWeight.Medium)
                                    Text(item.status, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                }
                                Text(item.time)
                            }
                        }
                    }
                }
            }

            if (role == "employee") {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                ) {
                    Column(
                        modifier = Modifier.padding(20.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Text("Absensi hari ini", fontWeight = FontWeight.SemiBold)
                        val attendance = data.my_attendance
                        val hasCheckedIn = attendance?.check_in_at != null
                        val hasCheckedOut = attendance?.check_out_at != null
                        Text(
                            when {
                                !hasCheckedIn -> "Belum check-in"
                                hasCheckedOut -> "Absensi hari ini selesai"
                                else -> "Sudah check-in · menunggu check-out"
                            },
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                        if (attendanceError.isNotBlank()) {
                            Text(attendanceError, color = MaterialTheme.colorScheme.error)
                        }
                        if (attendanceMessage.isNotBlank()) {
                            Text(attendanceMessage, color = MaterialTheme.colorScheme.primary)
                        }
                        if (!hasCheckedOut) {
                            Button(
                                onClick = { startAttendance(checkOut = hasCheckedIn) },
                                enabled = !isSubmittingAttendance,
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Text(
                                    when {
                                        isSubmittingAttendance -> "Memproses absensi..."
                                        hasCheckedIn -> "Absen pulang"
                                        else -> "Absen masuk"
                                    }
                                )
                            }
                            Text(
                                "Aplikasi akan meminta lokasi presisi dan foto selfie. Validasi geofence dilakukan server.",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }
            }

            Card(
                modifier = Modifier.fillMaxWidth(),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
            ) {
                Column(
                    modifier = Modifier.padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Text("Lokasi kerja", fontWeight = FontWeight.SemiBold)
                    if (data.locations.isEmpty()) {
                        Text("Belum ada lokasi kerja terdaftar.")
                    } else {
                        data.locations.forEach { location ->
                            Text(location.name, fontWeight = FontWeight.Medium)
                            Text(
                                "${location.branch_name ?: "Cabang tidak ditentukan"} · radius ${location.radius_meters} m",
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }
            }
        }

        OutlinedButton(
            onClick = { refreshKey++ },
            enabled = !isLoading,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(if (isLoading) "Memuat..." else "Muat ulang dashboard")
        }
    }
}

@Composable
fun StatCard(title: String, value: Int, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier,
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(title, style = MaterialTheme.typography.labelMedium)
            Spacer(modifier = Modifier.height(8.dp))
            Text(value.toString(), style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
        }
    }
}

private fun Exception.toUserMessage(action: String): String = when (this) {
    is IOException -> "$action: tidak bisa terhubung ke backend. Pastikan Laravel aktif di http://10.0.2.2:8001."
    is HttpException -> "$action: server membalas dengan HTTP ${code()}."
    else -> "$action: ${message ?: "terjadi kesalahan yang tidak diketahui"}"
}
