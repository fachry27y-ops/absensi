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

    @GET("/api/dashboard")
    suspend fun dashboard(@Header("Authorization") token: String): DashboardResponse
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
    val username: String,
    val role: String
)

data class DashboardResponse(
    val stats: Map<String, Int>,
    val recent: List<RecentItem>,
    val locations: List<LocationItem>
)

data class RecentItem(
    val name: String,
    val status: String,
    val time: String
)

data class LocationItem(
    val id: Long,
    val name: String,
    val branch_name: String,
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
