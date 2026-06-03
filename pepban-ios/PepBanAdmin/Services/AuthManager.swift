import Foundation
import Security

@Observable
class AuthManager {
    var isAuthenticated: Bool = false
    private(set) var token: String = ""

    private let tokenKey = "pepban_admin_token"

    init() {
        if let saved = Keychain.load(tokenKey), !saved.isEmpty {
            token = saved
            APIClient.shared.token = saved
            isAuthenticated = true
        }
    }

    func login(password: String) async throws {
        let result = try await APIClient.shared.login(password: password)
        token = result.token
        APIClient.shared.token = result.token
        isAuthenticated = true
        Keychain.save(tokenKey, value: result.token)
    }

    func logout() async {
        try? await APIClient.shared.logout()
        token = ""
        APIClient.shared.token = ""
        isAuthenticated = false
        Keychain.delete(tokenKey)
    }
}

enum Keychain {
    static func save(_ key: String, value: String) {
        guard let data = value.data(using: .utf8) else { return }
        let query: [String: Any] = [
            kSecClass as String:            kSecClassGenericPassword,
            kSecAttrAccount as String:      key,
            kSecValueData as String:        data,
            kSecAttrAccessible as String:   kSecAttrAccessibleAfterFirstUnlock,
        ]
        SecItemDelete(query as CFDictionary)
        SecItemAdd(query as CFDictionary, nil)
    }

    static func load(_ key: String) -> String? {
        let query: [String: Any] = [
            kSecClass as String:       kSecClassGenericPassword,
            kSecAttrAccount as String: key,
            kSecReturnData as String:  true,
            kSecMatchLimit as String:  kSecMatchLimitOne,
        ]
        var result: AnyObject?
        guard SecItemCopyMatching(query as CFDictionary, &result) == errSecSuccess,
              let data = result as? Data else { return nil }
        return String(data: data, encoding: .utf8)
    }

    static func delete(_ key: String) {
        let query: [String: Any] = [
            kSecClass as String:       kSecClassGenericPassword,
            kSecAttrAccount as String: key,
        ]
        SecItemDelete(query as CFDictionary)
    }
}
