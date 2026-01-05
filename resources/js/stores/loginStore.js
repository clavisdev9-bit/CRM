

import { defineStore } from "pinia";
import { ref } from "vue";
import axios from "axios";
import { useToast } from "vue-toastification";

export const exportsLoginStore = defineStore("login", () => {
  const email = ref(localStorage.getItem("remember_email") || ""); // auto fill
  const password = ref("");
  const errors = ref([]);
  const isLoading = ref(false);

  const honeypot = ref("");
  const toast = useToast();

  // AUTH STATE
  const user = ref(JSON.parse(localStorage.getItem("auth_user")) || null);
  const token = ref(localStorage.getItem("auth_token") || null);

  const LoginLogic = async (options = {}) => {
    errors.value = [];
    isLoading.value = true;

    // Bot protection
    if (honeypot.value.length > 0) {
      console.warn("SPAM BOT DETECTED");
      isLoading.value = false;
      return false;
    }

    try {
      const res = await axios.post("http://127.0.0.1:8000/api/signIn", {
        email: email.value,
        password: password.value,
      });

      toast.success(res.data.message || "Login Successful!");

      const remember = options.remember === true;

      // SAVE TOKEN (selalu localStorage)
      token.value = res.data.access_token;
      localStorage.setItem("auth_token", token.value);

      // SAVE USER (selalu localStorage)
      user.value = res.data.user;
      localStorage.setItem("auth_user", JSON.stringify(res.data.user));

      // REMEMBER ME — hanya simpan email
      if (remember) {
        localStorage.setItem("remember_email", email.value);
      } else {
        localStorage.removeItem("remember_email");
      }

      isLoading.value = false;
      return res.data;

    } catch (error) {
      isLoading.value = false;

      if (error.response) {
        const data = error.response.data;

        if (error.response.status === 422 && data.errors) {
          errors.value = data.errors;

          const firstError =
            data.errors.email?.[0] ||
            data.errors.password?.[0] ||
            "Validation failed.";

          toast.error(firstError);
          return false;
        }

        toast.error(data.message || "Login failed!");
        return false;
      }

      toast.error("Unable to connect to server.");
      return false;
    }
  };



// const fetchProfile = async () => {
//   if (!token.value) return null;

//   try {
//     const res = await axios.get("http://127.0.0.1:8000/api/get-profile", {
//       headers: {
//         Authorization: `Bearer ${token.value}`
//       }
//     });

//     // Simpan langsung data user saja
//     user.value = res.data.user;

//     localStorage.setItem("auth_user", JSON.stringify(user.value));

//     return user.value;
//   } catch (err) {
//     console.error("Failed to fetch profile:", err);
//     return null;
//   }
// };

const fetchProfile = async () => {
  if (!token.value) return null

  try {
    const res = await axios.get("/api/get-profile", {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (res.data.success) {
      user.value = res.data.user

      localStorage.setItem(
        "auth_user",
        JSON.stringify(res.data.user)
      )

      return res.data.user
    }

    return null
  } catch (err) {
    console.error("Failed to fetch profile:", err.response?.data || err)
    return null
  }
}



  return {
    email,
    password,
    errors,
    isLoading,
    honeypot,
    user,
    token,
    LoginLogic,
    fetchProfile,  
  };
});