package com.secureattend.data

import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST

interface AttendanceApi {
    @POST("/api/auth/login")
    suspend fun login(@Body request: LoginRequest): AuthResponse

    @POST("/api/auth/logout")
    suspend fun logout(@Header("Authorization") token: String)

    @GET("/api/dashboard")
    suspend fun dashboard(@Header("Authorization") token: String): DashboardResponse

    @POST("/api/attendance/check-in")
    suspend fun checkIn(
        @Header("Authorization") token: String,
        @Body request: AttendanceRequest
    ): AttendanceActionResponse

    @POST("/api/attendance/check-out")
    suspend fun checkOut(
        @Header("Authorization") token: String,
        @Body request: AttendanceRequest
    ): AttendanceActionResponse
}

data class LoginRequest(
    val email: String? = null,
    val username: String? = null,
    val password: String
)

data class AuthResponse(
    val message: String,
    val token: String,
    val user: UserSummary
)

data class UserSummary(
    val id: Long,
    val name: String,
    val email: String,
    val username: String?,
    val role: String
)

data class DashboardResponse(
    val stats: Map<String, Int>,
    val recent: List<RecentItem>,
    val locations: List<LocationItem>,
    val monthly: MonthlySummary? = null,
    val my_attendance: TodayAttendance? = null
)

data class AttendanceRequest(
    val latitude: Double,
    val longitude: Double,
    val accuracy: Float,
    val photo: String,
    val mock_location_detected: Boolean,
    val location_age_ms: Long
)

data class AttendanceActionResponse(
    val message: String,
    val attendance: TodayAttendance
)

data class TodayAttendance(
    val id: Long,
    val check_in_at: String?,
    val check_out_at: String?,
    val status: String
)

data class RecentItem(
    val name: String,
    val status: String,
    val time: String
)

data class MonthlySummary(
    val labels: List<String>,
    val values: List<Int>
)

data class LocationItem(
    val id: Long,
    val name: String,
    val branch_name: String?,
    val radius_meters: Int,
    val max_accuracy_meters: Int,
    val is_active: Boolean
)

object AttendanceApiFactory {
    private const val BASE_URL = "http://10.0.2.2:8001/"

    fun create(): AttendanceApi {
        val moshi = Moshi.Builder()
            .add(KotlinJsonAdapterFactory())
            .build()

        return Retrofit.Builder()
            .baseUrl(BASE_URL)
            .addConverterFactory(MoshiConverterFactory.create(moshi))
            .build()
            .create(AttendanceApi::class.java)
    }
}
