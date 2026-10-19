package com.secureattend

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import com.secureattend.ui.SecureAttendApp
import com.secureattend.ui.theme.SecureAttendTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            SecureAttendTheme {
                Surface(color = MaterialTheme.colorScheme.background) {
                    SecureAttendApp()
                }
            }
        }
    }
}
